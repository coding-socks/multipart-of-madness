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

