<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\StoreController;
use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\User\CartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes for authentication-related actions
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'Register')->name('auth.register');
    Route::post('/send-email-code', 'SendEmailCode')->name('auth.send.email.code');
    Route::post('/verify-email', 'VerifyEmail')->name('auth.verify.email');
    Route::post('/verify-phone-number', 'VerifyPhoneNumber')->name('auth.verify.phone');
    Route::post('/send-phone-number-code', 'SendPhoneNumberCode')->name('auth.send.phone.number.code');
    Route::post('/add-password', 'addPassword')->name('auth.add.password');
    Route::post('/add-information', 'addInformation')->name('auth.add.information');
    Route::post('/login', 'login')->name('auth.login');
    Route::post('/logout', 'logout')->name('auth.logout');
    Route::post('/forgot-password', 'forgotPassword')->name('auth.forgot.password');
});

Route::middleware('auth:sanctum')->group(function (){
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout')->name('auth.logout');
    });

    Route::prefix('store')->controller(StoreController::class)->group(function () {
        Route::post('/registered', 'registered')->name('store.registered');
        Route::post('/update', 'update')->name('store.update'); // Added {id} parameter
        Route::post('/update-status', 'updateStatus')->name('store.update-status'); // Added {id} parameter
        Route::get('/show/{id?}', 'show')->name('store.show'); // Changed to GET and added optional {id} parameter
        Route::post('/login', 'login')->name('store.login');
        Route::post('/logout', 'logout')->name('store.logout');
    });


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

Route::prefix('order')->controller(OrderController::class)->group(function (){
    Route::post('/create', 'order')->name('order.create');
    Route::get('/show', 'show')->name('order.show'); // Changed to GET for show
});


// Authenticated user route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
