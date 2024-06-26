<?php

namespace CodingSocks\MultipartOfMadness;

use Illuminate\Filesystem\AwsS3V3Adapter;

class MultipartOfMadness extends AwsS3V3Adapter
{
    public static function fromAdapter(AwsS3V3Adapter $adapter)
    {
        return new static(
            $adapter->getDriver(),
            $adapter->getAdapter(),
            $adapter->getConfig(),
            $adapter->getClient()
        );
    }

    /**
     * Get a temporary upload part URL for the file at the given path.
     *
     * @param  string  $path
     * @param  string  $uploadId
     * @param  string  $partNumber
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return array
     */
    public function temporaryUploadPartUrl($path, $uploadId, $partNumber, $expiration, array $options = [])
    {
        $command = $this->client->getCommand('UploadPart', array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
            'Body' => '',
        ], $options));

        $signedRequest = $this->client->createPresignedRequest(
            $command, $expiration, $options
        );

        $uri = $signedRequest->getUri();

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['temporary_url'])) {
            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
        }

        return [
            'url' => (string) $uri,
            'headers' => $signedRequest->getHeaders(),
        ];
    }

    /**
     * Lists the parts that have been uploaded for a specific multipart upload.
     *
     * @param  string  $path
     * @param  array  $args
     * @return \Aws\Result
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListParts.html
     */
    public function listParts($path, array $args = [])
    {
        return $this->client->listParts(array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $args));
    }

    /**
     * Initiates a multipart upload.
     *
     * @param  string  $path
     * @param  array  $args
     * @return \Aws\Result
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     */
    public function createMultipartUpload($path, array $args = [])
    {
        return $this->client->createMultipartUpload(array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $args));
    }

    /**
     * Completes a multipart upload by assembling previously uploaded parts.
     *
     * @param string $path
     * @param array $args
     * @return \Aws\Result
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CompleteMultipartUpload.html
     */
    public function completeMultipartUpload($path, array $args = [])
    {
        return $this->client->completeMultipartUpload(array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $args));
    }

    /**
     * Aborts a multipart upload.
     *
     * @param string $path
     * @param array $args
     * @return \Aws\Result
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_AbortMultipartUpload.html
     */
    public function abortMultipartUpload($path, array $args = [])
    {
        return $this->client->abortMultipartUpload(array_merge([
            'Bucket' => $this->config['bucket'],
            'Key' => $this->prefixer->prefixPath($path),
        ], $args));
    }
}