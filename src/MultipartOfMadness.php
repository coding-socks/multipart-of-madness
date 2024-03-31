<?php

namespace CodingSocks\MultipartOfMadness;

use Illuminate\Filesystem\AwsS3V3Adapter;

class MultipartOfMadness extends AwsS3V3Adapter
{
    public function __construct(AwsS3V3Adapter $adapter)
    {
        parent::__construct(
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

    public function listParts(array $args = [])
    {
        return $this->client->listParts($args);
    }

    public function createMultipartUpload(array $args = [])
    {
        return $this->client->createMultipartUpload($args);
    }

    public function completeMultipartUpload(array $args = [])
    {
        return $this->client->completeMultipartUpload($args);
    }

    public function abortMultipartUpload(array $args = [])
    {
        return $this->client->abortMultipartUpload($args);
    }
}