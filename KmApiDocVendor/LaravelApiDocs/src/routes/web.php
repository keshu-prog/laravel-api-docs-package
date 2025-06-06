<?php

use Illuminate\Support\Facades\Route;
use KmApiDocVendor\LaravelApiDocs\Http\Controllers\DocsController;

Route::get('/api/docs', [DocsController::class, 'index']);
