<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\User\AddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes for authentication-related actions
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->name('auth.register');
    Route::post('/verify-email', 'verifyEmail')->name('auth.verify.email');
    Route::post('/verify-phone-number', 'verifyPhoneNumber')->name('auth.verify.phone');
    Route::post('/send-email-code', 'sendEmailCode')->name('auth.send.email.code');
    Route::post('/send-phone-number-code', 'sendPhoneNumberCode')->name('auth.send.phone.number.code');
    Route::post('/add-password', 'addPassword')->name('auth.add.password');
    Route::post('/add-phone-number', 'addPhoneNumber')->name('auth.add.phone.number');
    Route::post('/add-information', 'addInformation')->name('auth.add.information');
    Route::post('/login', 'login')->name('auth.login');
    Route::post('/forgot-password', 'forgotPassword')->name('auth.forgot.password');
});

// Routes for address-related actions
Route::prefix('address')->controller(AddressController::class)->group(function () {
    Route::post('/create', 'create')->name('address.create');
    Route::put('/update/{id}', 'update')->name('address.update'); // Changed to PUT for update
    Route::delete('/delete/{id}', 'delete')->name('address.delete'); // Changed to DELETE for delete
    Route::get('/show/{id}', 'show')->name('address.show'); // Changed to GET for show
});

// Authenticated user route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
