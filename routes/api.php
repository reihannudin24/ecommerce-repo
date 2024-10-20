<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\User\CartController;
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

// Routes for product-related actions
Route::prefix('product')->controller(ProductController::class)->group(function () {
    Route::post('/create', 'create')->name('product.create');
    Route::put('/update/{id}', 'update')->name('product.update'); // Changed to PUT for update
    Route::delete('/delete/{id}', 'delete')->name('product.delete'); // Changed to DELETE for delete
    Route::post('/create/type', 'createTyper')->name('product.type.create');
    Route::put('/update/type/{id}', 'updateTyper')->name('product.type.update'); // Changed to PUT for update
    Route::delete('/delete/type/{id}', 'deleteTyper')->name('product.type.delete'); // Changed to DELETE for delete

    Route::get('/show/{id}', 'show')->name('address.show'); // Changed to GET for show
});


Route::prefix('store')->controller(StoreController::class)->group(function () {
    Route::post('/registered', 'registered')->name('store.registered');
    Route::post('/update', 'update')->name('store.update');
    Route::post('/update-status', 'updateStatus')->name('store.update-status');
    Route::post('/show', 'show')->name('store.show');
    Route::post('/login', 'login')->name('store.login');
    Route::post('/logout', 'logout')->name('store.logout');
});


Route::prefix('cart')->controller(CartController::class)->group(function (){
    Route::post('/create', 'create')->name('cart.create');
    Route::put('/update', 'update')->name('cart.update'); // Changed to PUT for update
    Route::delete('/delete', 'delete')->name('cart.delete'); // Changed to DELETE for delete
    Route::get('/show', 'show')->name('cart.show'); // Changed to GET for show
});

Route::prefix('favorite')->controller(CartController::class)->group(function (){
    Route::post('/create', 'create')->name('favorite.create');
    Route::delete('/delete', 'delete')->name('favorite.delete'); // Changed to DELETE for delete
    Route::get('/show', 'show')->name('favorite.show'); // Changed to GET for show
});


// Authenticated user route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
