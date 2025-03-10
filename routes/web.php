<?php

use Illuminate\Foundation\Application;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::middleware([
//     'auth:sanctum',
//     config('jetstream.auth_session'),
//     'verified',
// ])->group(function () {
//     Route::get('/dashboard', function () {
//         return Inertia::render('Dashboard');
//     })->name('dashboard');

Route::get('/', [
    \App\Http\Controllers\UsdtPaymentController::class,
    'showPayment',
])->name('usdt-payment');

Route::post('/usdt-payment', [
    \App\Http\Controllers\UsdtPaymentController::class,
    'initiatePayment',
]);

Route::get('/pay', [
    \App\Http\Controllers\UsdtPaymentController::class,
    'pay',
])->name('pay-usdt');

Route::get('/status', [
    \App\Http\Controllers\UsdtPaymentController::class,
    'checkStatus',
]);


    // USDT Payment routes
    Route::get('/bep20', [
        \App\Http\Controllers\Bep20UsdtPaymentController::class,
        'showPayment',
    ])->name('usdt-bep20-payment');
    
    Route::post('/usdt-bep20', [
        \App\Http\Controllers\Bep20UsdtPaymentController::class,
        'initiatePayment',
    ]);
    
    Route::get('/pay-bep20', [
        \App\Http\Controllers\Bep20UsdtPaymentController::class,
        'pay',
    ])->name('pay-bep20');
    
    Route::get('/check_bep20_status', [
        \App\Http\Controllers\Bep20UsdtPaymentController::class,
        'checkStatus',
    ]);