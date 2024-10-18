<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\User\AddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
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


Route::prefix('address')->group(function (){
   Route::post('/create', [AddressController::class, 'create'])->name('address.create');
   Route::post('/update/{id}', [AddressController::class, 'update'])->name('address.update');
   Route::post('/delete/{id}', [AddressController::class, 'delete'])->name('address.delete');
   Route::post('/show/{id}', [AddressController::class, 'show'])->name('address.show');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

