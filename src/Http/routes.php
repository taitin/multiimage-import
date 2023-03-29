<?php

use Taitin\MultiimageImport\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('multiimage-import', Controllers\MultiimageImportController::class . '@index');

Route::get('multiimage-import/files_handle', Controllers\MultiimageImportController::class . '@filesHandle');
Route::get('multiimage-import/batch_handle', Controllers\MultiimageImportController::class . '@batchHandle');
