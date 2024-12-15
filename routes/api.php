<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/google-auth', [AuthenticationController::class, 'authGoogle']);
Route::post('/register', [AuthenticationController::class, 'register']);
Route::post('/resend-otp', [AuthenticationController::class, 'resendOtp']);
Route::post('/check-otp-register', [AuthenticationController::class, 'verifyOtp']);
Route::post('/verify-register', [AuthenticationController::class, 'verifyRegister']);

Route::prefix('forgot-password')->group(function () {
    Route::post('/request-otp', [ForgotPasswordController::class, 'requestOtp']);
    Route::post('/resend-otp', [ForgotPasswordController::class, 'resendOtp']);
    Route::post('/check-otp', [ForgotPasswordController::class, 'checkOtp']);
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);
});

Route::post('/login', [AuthenticationController::class, 'login']);

Route::get('/slider', [HomeController::class, 'getSlider']);
Route::get('/category', [HomeController::class, 'getCategory']);

Route::get('/product', [HomeController::class, 'getProduct']);
Route::get('/product/{slug}', [HomeController::class, 'GetProductDetail']);
Route::get('/product/{slug}/review', [HomeController::class, 'getProductReview']);
Route::get('/seller/{username}', [HomeController::class, 'getSellerDetail']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'getProfile']);
    Route::patch('/update-profile', [ProfileController::class, 'updateProfile']);

    Route::apiResource('address', AddressController::class);
    Route::post('address/{uuid}/set-default', [AddressController::class, 'setDefault']);

    Route::get('province', [AddressController::class, 'getProvince']);
    Route::get('city', [AddressController::class, 'getCity']);
});
