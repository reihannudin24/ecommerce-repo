<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('group')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/verify-email', [AuthController::class, 'VerifyEmail'])->name('verify.email');
    Route::post('/verify-phone-number', [AuthController::class, 'VerifyPhoneNumber'])->name('verify.phone');
    Route::post('/send-email-code', [AuthController::class, 'SendEmailCode'])->name('send.email.code');
    Route::post('/send-phone-number-code', [AuthController::class, 'SendPhoneNumberCode'])->name('send.phone.number.code');
    Route::post('/add-password', [AuthController::class, 'AddPassword'])->name('add.password');
    Route::post('/add-phone-number', [AuthController::class, 'AddPhoneNumber'])->name('add.phone.number');
    Route::post('/add-information', [AuthController::class, 'AddInformation'])->name('add.information');
    Route::post('/login', [AuthController::class, 'Login'])->name('login');
    Route::post('/forgot-password', [AuthController::class, 'ForgotPassword'])->name('forgot.password');
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

