# Multipart of Madness

This package helps to integrate your library with `@uppy/aws-s3-multipart`.

## Installation

You can easily install this package using Composer.

```
composer require coding-socks/multipart-of-madness --dev
```

You have to install `@uppy/aws-s3-multipart` to be able to use it as an uploader. 

```
npm i -D @uppy/aws-s3-multipart
```

## Usage

Configure Uppy with `AwsS3Multipart` uploader.

```javascript
uppy.use(AwsS3Multipart, {
    companionUrl: '/uppy',
    shouldUseMultipart(file) {
        return file.size > 10 * 1024 * 1024;
    }
})
uppy.addPreProcessor(() => {
    return new Promise((resolve) => {
        const cookieName = window.axios.defaults.xsrfCookieName
        const headerName = window.axios.defaults.xsrfHeaderName
        /** @type {AwsS3Multipart} */
        const plugin = uppy.getPlugin('AwsS3Multipart')
        plugin?.setOptions({
            // You have to implement `cookies.read`
            companionHeaders: {[headerName]: cookies.read(cookieName)},
        })
        resolve()
    })
})
```

Configure the CORS of your S3 or S3-compatible solution. It needs to allow GET and PUT requests from your domain and expose some unsafe HTTP headers to Uppy. Example:

```json
[
	{
		"AllowedOrigins": ["https://my-app.com"],
		"AllowedMethods": ["GET", "PUT"],
		"MaxAgeSeconds": 3000,
		"AllowedHeaders": [
			"Authorization",
			"x-amz-date",
			"x-amz-content-sha256",
			"content-type"
		],
		"ExposeHeaders": ["ETag", "Location"]
	}
]
```

## Defaults

The expiration times for the signe links is `15 minutes`.

The target filesystem disk is `s3`.

The endpoint routes registered under `web` route with `/uppy` prefix.

## Implementation

This implementation uses PutObject command instead of PostObject on
non-multipart upload request. This is only for compatibility with
[Backblaze B2] and [Cloudflare R2]. `@uppy/companion` uses PostObject.

All other routes were implemented based on `@uppy/companion`.

[Backblaze B2]: https://www.backblaze.com/cloud-storage
[Cloudflare R2]: https://www.cloudflare.com/developer-platform/r2/
