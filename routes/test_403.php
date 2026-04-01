<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-403-superadmin', function () {
    abort(403);
})->prefix('superadmin');

Route::get('/test-403-tenant', function () {
    abort(403);
})->prefix('tenant');
