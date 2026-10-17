<?php

use App\Http\Controllers\Api\StorefrontApiController;
use Illuminate\Support\Facades\Route;

Route::get('/search/autocomplete', [StorefrontApiController::class, 'searchAutocomplete'])
    ->name('api.search.autocomplete');

Route::get('/check-phone', [StorefrontApiController::class, 'checkPhone'])
    ->name('api.check-phone');

Route::get('/mini-cart', [StorefrontApiController::class, 'getMiniCart'])
    ->name('api.mini-cart');

Route::post('/cart/add/{product}', [StorefrontApiController::class, 'addToCart'])
    ->name('api.cart.add');

Route::post('/cart/update', [StorefrontApiController::class, 'updateCart'])
    ->name('api.cart.update');

Route::post('/cart/remove', [StorefrontApiController::class, 'removeFromCart'])
    ->name('api.cart.remove');

Route::get('/header-counts', [StorefrontApiController::class, 'headerCounts'])
    ->name('api.header-counts');

Route::get('/products/{product}/reviews', [StorefrontApiController::class, 'productReviews'])
    ->name('api.product.reviews');

Route::post('/checkout/coupon', [StorefrontApiController::class, 'checkCoupon'])
    ->middleware('throttle:15,1')
    ->name('api.checkout.coupon');
