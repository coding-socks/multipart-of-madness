<?php

use CodingSocks\MultipartOfMadness\Http\Controller\S3MultipartController;

Route::get('/s3/params', [S3MultipartController::class, 'uploadParameters'])
    ->name('uploadParameters');
Route::post('/s3/multipart', [S3MultipartController::class, 'create'])
    ->name('create');
Route::get('/s3/multipart/{uploadId}', [S3MultipartController::class, 'uploadedParts'])
    ->name('uploadedParts');
Route::get('/s3/multipart/{uploadId}/batch', [S3MultipartController::class, 'batchSignParts'])
    ->name('batchSignParts');
Route::post('/s3/multipart/{uploadId}/complete', [S3MultipartController::class, 'complete'])
    ->name('complete');
Route::get('/s3/multipart/{uploadId}/{partNumber}', [S3MultipartController::class, 'signPart'])
    ->whereNumber('signPart')->name('create');
Route::delete('/s3/multipart/{uploadId}', [S3MultipartController::class, 'abort'])
    ->name('abort');
