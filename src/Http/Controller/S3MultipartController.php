<?php

namespace CodingSocks\MultipartOfMadness\Http\Controller;

use Carbon\CarbonInterval;
use CodingSocks\MultipartOfMadness\Http\Rules\S3MetadataRule;
use CodingSocks\MultipartOfMadness\MultipartOfMadness;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class S3MultipartController extends Controller
{
    public function __construct(MultipartOfMadness $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Get upload paramaters for a simple direct upload.
     *
     * Expected query parameters:
     *  - filename - The name of the file, given to the `config.getKey`
     *    option to determine the object key name in the S3 bucket.
     *  - type - The MIME type of the file.
     *  - metadata - Key/value pairs configuring S3 metadata. Both must be ASCII-safe.
     *    Query parameters are formatted like `metadata[name]=value`.
     *
     * Response JSON:
     *  - method - The HTTP method to use to upload.
     *  - url - The URL to upload to.
     *  - fields - Form fields to send along.
     */
    public function uploadParameters(Request $request)
    {
        $data = $request->validate([
            'filename' => ['required', 'string'],
            'type' => ['required', 'string'],
            'metadata' => [new S3MetadataRule],
        ]);

        $params = [
            'success_action_status' => '201',
            'content-type' => $data['type'],
        ];

        // TODO: read optional ACL from config

        foreach ($data['metadata'] as $key => $value) {
            $params["x-amz-meta-{$key}"] = $value;
        }

        $key = implode('-', [Str::random(), $data['filename']]);
        $expires = config('multipart-of-madness.expiration_time');

        $result = $this->adapter->temporaryUploadUrl($key, now()->add($expires), $params);

        return Response::json([
            'method' => 'PUT',
            'url' => $result['url'],
            'fields' => ['key' => $key],
            'expires' => CarbonInterval::make($expires)->totalSeconds,
        ]);
    }

    /**
     * Create an S3 multipart upload. With this, files can be uploaded in chunks of 5MB+ each.
     *
     * Expected JSON body:
     *  - filename - The name of the file, given to the `config.getKey`
     *    option to determine the object key name in the S3 bucket.
     *  - type - The MIME type of the file.
     *  - metadata - An object with the key/value pairs to set as metadata.
     *    Keys and values must be ASCII-safe for S3.
     *
     * Response JSON:
     *  - key - The object key in the S3 bucket.
     *  - uploadId - The ID of this multipart upload, to be used in later requests.
     */
    public function create(Request $request)
    {
        $data = $request->validate([
            'filename' => ['required', 'string'],
            'type' => ['required', 'string'],
            'metadata' => [new S3MetadataRule],
        ]);

        $key = implode('-', [Str::random(), $data['filename']]);

        $params = [
            'Bucket' => $this->adapter->getConfig()['bucket'],
            'Key' => $this->adapter->path($key),
            'ContentType' => $data['type'],
            'Metadata' => $this->encodeMetadata($data['metadata']),
        ];

        // TODO: read optional ACL from config

        $result = $this->adapter->createMultipartUpload($params);

        return Response::json([
            'key' => $result['Key'],
            'uploadId' => $result['UploadId'],
        ]);
    }

    /**
     * List parts that have been fully uploaded so far.
     *
     * Expected URL parameters:
     *  - uploadId - The uploadId returned from `createMultipartUpload`.
     * Expected query parameters:
     *  - key - The object key in the S3 bucket.
     * Response JSON:
     *  - An array of objects representing parts:
     *     - PartNumber - the index of this part.
     *     - ETag - a hash of this part's contents, used to refer to it.
     *     - Size - size of this part.
     */
    public function uploadedParts(Request $request)
    {
        $uploadId = $request->route('uploadId');
        $data = Validator::make($request->query(), ['key' => ['required', 'string']])->validate();
        $key = $data['key'];

        $parts = Collection::make();
        $partIndex = 0;
        do {
            $result = $this->adapter->listParts([
                'Bucket' => $this->adapter->getConfig()['bucket'],
                'Key' => $this->adapter->path($key),
                'UploadId' => $uploadId,
                'PartNumberMarker' => $partIndex,
            ]);

            if ($result['Parts'] ?? false) {
                $parts->concat($result['Parts']);
            }

            $truncated = $result['IsTruncated'] ?? false;
            $partIndex = $result['NextPartNumberMarker'] ?? -1;
        } while ($truncated);

        return Response::json($parts);
    }

    /**
     * Get parameters for uploading one part.
     *
     * Expected URL parameters:
     *  - uploadId - The uploadId returned from `createMultipartUpload`.
     *  - partNumber - This part's index in the file (1-10000).
     * Expected query parameters:
     *  - key - The object key in the S3 bucket.
     * Response JSON:
     *  - url - The URL to upload to, including signed query parameters.
     */
    public function signPart(Request $request)
    {
        $uploadId = $request->route('uploadId');
        $partNumber = $request->route('partNumber');
        $data = Validator::make($request->query(), ['key' => ['required', 'string']])->validate();
        $key = $data['key'];

        $expires = config('multipart-of-madness.expiration_time');
        ['url' => $url] = $this->adapter->temporaryUploadPartUrl($key, $uploadId, $partNumber, now()->add($expires));

        return Response::json([
            'url' => $url,
            'expires' => CarbonInterval::make($expires)->totalSeconds,
        ]);
    }

    /**
     * Get parameters for uploading a batch of parts.
     *
     * Expected URL parameters:
     *  - uploadId - The uploadId returned from `createMultipartUpload`.
     * Expected query parameters:
     *  - key - The object key in the S3 bucket.
     *  - partNumbers - A comma separated list of part numbers representing
     *                  indecies in the file (1-10000).
     * Response JSON:
     *  - presignedUrls - The URLs to upload to, including signed query parameters,
     *                    in an object mapped to part numbers.
     */
    public function batchSignParts(Request $request)
    {
        $uploadId = $request->route('uploadId');
        $data = Validator::make($request->query(), [
            'key' => ['required', 'string'],
            'partNumbers' => ['required', 'string', 'regex:/^(10000|[1-9][0-9]{0,3})(\,(10000|[1-9][0-9]{0,3}))*$/']
        ])->validate();
        $key = $data['key'];
        $partNumbers = explode(',', $data['partNumbers']);

        $presignedUrls = [];
        $expires = config('multipart-of-madness.expiration_time');
        foreach ($partNumbers as $partNumber) {
            ['url' => $url] = $this->adapter->temporaryUploadPartUrl($key, $uploadId, $partNumber, now()->add($expires));
            $presignedUrls[$partNumber] = $url;
        }

        return Response::json([
            'presignedUrls' => $presignedUrls,
        ]);
    }

    /**
     * Abort a multipart upload, deleting already uploaded parts.
     *
     * Expected URL parameters:
     *  - uploadId - The uploadId returned from `createMultipartUpload`.
     * Expected query parameters:
     *  - key - The object key in the S3 bucket.
     * Response JSON:
     *   Empty.
     */
    public function abort(Request $request)
    {
        $uploadId = $request->route('uploadId');
        $data = Validator::make($request->query(), ['key' => ['required', 'string']])->validate();
        $key = $data['key'];

        $this->adapter->abortMultipartUpload([
            'Bucket' => $this->adapter->getConfig()['bucket'],
            'Key' => $this->adapter->path($key),
            'UploadId' => $uploadId,
        ]);

        return Response::json();
    }

    /**
     * Complete a multipart upload, combining all the parts into a single object in the S3 bucket.
     *
     * Expected URL parameters:
     *  - uploadId - The uploadId returned from `createMultipartUpload`.
     * Expected query parameters:
     *  - key - The object key in the S3 bucket.
     * Expected JSON body:
     *  - parts - An array of parts, see the `getUploadedParts` response JSON.
     * Response JSON:
     *  - location - The full URL to the object in the S3 bucket.
     */
    public function complete(Request $request)
    {
        $uploadId = $request->route('uploadId');
        $data = Validator::make($request->query(), ['key' => ['required', 'string']])->validate();
        $key = $data['key'];

        $data = $request->validate([
            'parts' => ['required', 'array', 'min:1'],
            'parts.*.PartNumber' => ['required', 'integer'],
            'parts.*.ETag' => ['required', 'string'],
        ]);

        $result = $this->adapter->completeMultipartUpload([
            'Bucket' => $this->adapter->getConfig()['bucket'],
            'Key' => $this->adapter->path($key),
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $data['parts'],
            ],
        ]);

        return Response::json([
            'location' => $result['Location'],
        ]);
    }

    /**
     * @param array $arr
     * @return array
     */
    protected function encodeMetadata($arr)
    {
        if (!is_array($arr)) {
            return $arr;
        }
        return collect($arr)->mapWithKeys(function ($value, $key) {
            return [$this->encodeIfNonAscii($key) => $this->encodeIfNonAscii($value)];
        })->toArray();
    }

    /**
     * @param string $str
     * @return string|null
     */
    protected function encodeIfNonAscii($str)
    {
        $original = mb_internal_encoding();
        mb_internal_encoding('UTF-8');
        $str = mb_encode_mimeheader($str, 'UTF-8', 'B');
        if ($original !== 'UTF-8') {
            mb_internal_encoding($original);
        }
        return $str;
    }
}