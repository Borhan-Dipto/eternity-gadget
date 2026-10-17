<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewCaptchaController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::get('/storage/{path}', [StorageController::class, 'serve'])
    ->where('path', '.*')
    ->name('storage.serve');

Route::get('/thumb/{width}/{path}', [\App\Http\Controllers\ThumbnailController::class, 'show'])
    ->whereNumber('width')->where('path', '.*')->name('media.thumb');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/captcha/review', [ReviewCaptchaController::class, 'show'])->name('captcha.review');
Route::get('/captcha/register', [ReviewCaptchaController::class, 'showRegister'])->name('captcha.register');

Route::controller(CatalogController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/search', 'search')->name('search');
    Route::get('/category/{slug}', 'category')->name('category.show');
    Route::get('/product/{slug}', 'product')->name('product.show');
    Route::post('/product/{product}/review', 'submitReview')->name('product.review');
    Route::post('/product/{product}/question', 'submitQuestion')->name('product.question');
    Route::post('/product/{product}/stock-alert', 'stockAlert')->name('product.stock-alert');
    Route::post('/product/{product}/compare', 'toggleComparison')->name('product.compare');
    Route::get('/compare', [AccountController::class, 'compare'])->name('compare');
});

Route::controller(StorefrontController::class)->group(function () {
    Route::get('/page/{slug}', 'page')->name('page.show');
    Route::get('/locale/{locale}', 'setLocale')->name('locale.switch');

    Route::get('/cart', 'cart')->name('cart');
    Route::post('/cart/add/{product}', 'addToCart')->name('cart.add');
    Route::post('/cart/update', 'updateCart')->name('cart.update');
    Route::post('/cart/remove', 'removeFromCart')->name('cart.remove');
    Route::post('/cart/save-for-later', 'saveForLater')->name('cart.save-for-later');
    Route::post('/cart/move-to-cart', 'moveToCart')->name('cart.move-to-cart');
    Route::get('/checkout', 'checkout')->name('checkout');
    Route::post('/checkout', 'placeOrder')->middleware('throttle:checkout')->name('checkout.place');
    Route::get('/order/{order}/confirmed', 'orderConfirmed')->name('order.confirmed')->middleware('signed');

    Route::get('/wishlist', 'wishlist')->name('wishlist');
    Route::post('/wishlist/{product}', 'toggleWishlist')->name('wishlist.toggle');
    Route::post('/newsletter', 'newsletter')->name('newsletter');
});

Route::get('/account', [AccountController::class, 'account'])->name('account');

Route::prefix('account')->name('account.')->group(function () {
    Route::controller(AccountController::class)->group(function () {
        Route::get('/login', 'showLogin')->middleware('guest')->name('login');
        Route::post('/login', 'login')->middleware(['guest', 'throttle:6,1'])->name('login.submit');
        Route::get('/register', 'showRegister')->middleware('guest')->name('register');
        Route::post('/register', 'register')->middleware(['guest', 'throttle:6,1'])->name('register.submit');

        Route::get('/phone/register', 'showPhoneRegister')->middleware('guest')->name('phone.register');
        Route::post('/phone/register', 'registerPhone')->middleware(['guest', 'throttle:6,1'])->name('phone.register.submit');
        Route::post('/phone/verify', 'verifyPhoneOtp')->middleware(['guest', 'throttle:6,1'])->name('phone.verify');

        Route::get('/phone/login', 'showPhoneLogin')->middleware('guest')->name('phone.login');
        Route::post('/phone/login', 'loginPhone')->middleware(['guest', 'throttle:6,1'])->name('phone.login.submit');

        Route::get('/forgot-password', 'showForgotPassword')->middleware('guest')->name('password.request');
        Route::post('/forgot-password', 'forgotPassword')->middleware(['guest', 'throttle:6,1'])->name('password.email');
        Route::get('/reset-password/{token}', 'showResetPassword')->middleware('guest')->name('password.reset');
        Route::post('/reset-password', 'resetPassword')->middleware(['guest', 'throttle:6,1'])->name('password.update');

        Route::get('/contact', 'contact')->name('contact');
        Route::post('/contact', 'storeContact')->middleware('throttle:10,1')->name('contact.store');

        Route::get('/guest-tracking', 'guestOrderTracking')->name('guest-tracking');
        Route::post('/guest-tracking', 'lookupGuestOrder')->middleware('throttle:10,1')->name('guest-tracking.lookup');

        Route::post('/logout', 'logout')->middleware('auth')->name('logout');
        Route::post('/convert-guest/{order}', 'convertGuest')->middleware('guest')->name('convert-guest');

        Route::middleware('auth')->group(function () {
            Route::get('/profile', 'profile')->name('profile');
            Route::post('/profile', 'updateProfile')->name('profile.update');
            Route::get('/password', 'showPassword')->name('password');
            Route::post('/password', 'updatePassword')->name('password.change');
            Route::post('/delete', 'deleteAccount')->name('delete');
            Route::post('/logout-all', 'logoutAllDevices')->name('logout-all');

            Route::get('/orders', 'orderHistory')->name('orders');
            Route::get('/reward-points', 'rewardPoints')->name('rewards');
            // Old pages that did not apply to this shop (no downloads, no card payments): send to the account page
            Route::redirect('/downloads', '/account', 301);
            Route::redirect('/recurring-payments', '/account', 301);
            Route::redirect('/transactions', '/account', 301);
            Route::redirect('/payment-methods', '/account', 301);
            Route::get('/newsletter', 'newsletterSettings')->name('newsletter');
            Route::post('/newsletter', 'updateNewsletterSettings')->name('newsletter.update');

            Route::get('/addresses', 'addresses')->name('addresses');
            Route::post('/addresses', 'storeAddress')->name('addresses.store');
            Route::put('/addresses/{address}', 'updateAddress')->name('addresses.update');
            Route::delete('/addresses/{address}', 'destroyAddress')->name('addresses.destroy');
            Route::post('/addresses/{address}/default', 'setDefaultAddress')->name('addresses.default');

            Route::get('/notifications', 'notifications')->name('notifications');
            Route::post('/notifications/{notification}/read', 'markNotificationRead')->name('notifications.read');
            Route::post('/notifications/read-all', 'markAllNotificationsRead')->name('notifications.read-all');
        });
    });
});

Route::middleware('auth')->prefix('account')->group(function () {
    Route::get('/email/verify', [AccountController::class, 'verifyEmailNotice'])->name('verification.notice');
    Route::post('/email/verification-notification', [AccountController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::get('/account/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect()->route('account')->with('success', 'Email verified successfully.');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'google|facebook')
    ->middleware('guest')
    ->name('auth.social.redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'google|facebook')
    ->middleware('guest')
    ->name('auth.social.callback');

Route::controller(OrderController::class)->prefix('orders')->name('orders.')->group(function () {
    Route::get('/{order}', 'show')->name('show');
    Route::get('/{order}/invoice', 'invoice')->name('invoice');
    Route::post('/{order}/cancel', 'cancel')->name('cancel');

    Route::middleware('auth')->group(function () {
        Route::post('/{order}/reorder', 'reorder')->name('reorder');
        Route::post('/{order}/confirm-delivery', 'confirmDelivery')->name('confirm-delivery');
    });
});
