<?php

use CranleighSchool\DomainExpiry\Http\Controllers\DomainExpiryController;
use Illuminate\Support\Facades\Route;

if (! config('domain-expiry.dashboard.enabled', true)) {
    return;
}

$prefix = config('domain-expiry.dashboard.uri', '/domain-expiry');
$middleware = config('domain-expiry.dashboard.middleware', ['web']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('domain-expiry.')
    ->group(function () {
        Route::get('/', [DomainExpiryController::class, 'index'])->name('index');
        Route::get('/json', [DomainExpiryController::class, 'json'])->name('json');
        Route::post('/refresh', [DomainExpiryController::class, 'refresh'])->name('refresh');
        Route::post('/refresh-all', [DomainExpiryController::class, 'refreshAll'])->name('refresh-all');
    });
