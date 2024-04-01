<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multipart of Madness Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for Multipart of Madness.
    | You are free to adjust these settings as needed.
    |
    | The expiry time defines how long each upload link will be considered valid.
    | This security feature keeps links short-lived so they have less time
    | to be guessed. You may change this as needed.
    |
    */

    'expiration_time' => \DateInterval::createFromDateString('15 minutes'),

    'storage_disk' => env('MULTIPART_OF_MADNESS_DISK', 's3'),

    /*
    |
    | Some providers does not allow signed urls with additional metadata.
    | By toggling this value, you can enable or disable of adding them
    | to the signed URL.
    |
    */

    'allow_metadata' => false,

    /*
    |--------------------------------------------------------------------------
    | Default ACL
    |--------------------------------------------------------------------------
    |
    | This option controls the default ACL that will be used by the controller
    | when signed URL needs to be created. You may set this to any of the
    | canned ACLs available in AWS S3.
    |
    | https://docs.aws.amazon.com/AmazonS3/latest/userguide/acl-overview.html#canned-acl
    |
    */

    // 'acl' => 'public-read',

    /*
    |-------------------------------------
    | Routes configurations
    |-------------------------------------
    */

    'routes' => [
        'name' => 'uppy.',
        'prefix' => 'uppy',
        'middleware' => ['web', 'auth'],
        'namespace' => 'CodingSocks\MultipartOfMadness\Http\Controllers',
    ],

];

