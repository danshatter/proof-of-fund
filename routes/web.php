<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, PaystackWebhookController, QoreIdWebhookController};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify-email', [AuthController::class, 'verifyEmail'])->name('auth.verify-email');

/**
 * The Paystack webhook
 */
Route::post('/paystack/webhook', PaystackWebhookController::class)->name('paystack-webhook');

/**
 * The Qore ID webhook
 */
Route::post('/qore-id/webhook', QoreIdWebhookController::class)->name('qore-id-webhook');