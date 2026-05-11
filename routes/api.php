<?php

use App\Http\Controllers\Api\AdTrackingController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::post('/ad-click/{ad}', [AdTrackingController::class, 'click'])->name('ad.click');
    Route::post('/ad-impression/{ad}', [AdTrackingController::class, 'impression'])->name('ad.impression');
});
