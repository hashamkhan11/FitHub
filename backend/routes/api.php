<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\MemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/member', function (Request $request) {
        return $request->user();
    });

    Route::get('/member/qr', [MemberController::class, 'qr']);
    Route::get('/member/membership', [MemberController::class, 'membership']);
    Route::post('/member/fcm-token', [MemberController::class, 'updateFcmToken']);
    Route::get('/member/measurements', [MemberController::class, 'measurements']);
    Route::post('/member/measurements', [MemberController::class, 'storeMeasurement']);

    Route::get('/classes', [ClassController::class, 'index']);
    Route::post('/classes/{class}/book', [ClassController::class, 'book']);
    Route::post('/bookings/{booking}/cancel', [ClassController::class, 'cancel']);
});
