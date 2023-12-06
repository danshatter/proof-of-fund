<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AccountController, AgencyController, ApplicationController, AuthController, BalanceController, BankController, DeclinedApplicationsController, IncomingPaymentsController, IndividualAgentController, LoanCalculatorController, OptionController, PendingApplicationsController, ReferralCodeController, ReferralListController, TenureController, TestController, TransactionController, UserController};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test', [TestController::class, 'index']);


Route::name('options')
    ->prefix('/options')
    ->controller(OptionController::class)
    ->group(function() {

        Route::get('', 'index')->name('.index');
        Route::get('/{option}', 'show')->name('.show');
    });

Route::name('tenures')
    ->prefix('/tenures')
    ->controller(TenureController::class)
    ->group(function() {

        Route::get('', 'index')->name('.index');
        Route::get('/{tenure}', 'show')->name('.show');
    });

Route::name('banks')
    ->prefix('/banks')
    ->controller(BankController::class)
    ->group(function() {

        Route::get('', 'index')->name('.index');
        Route::get('/{bankCode}', 'show')->name('.show');
    });

Route::name('accounts')
    ->prefix('/accounts')
    ->controller(AccountController::class)
    ->group(function() {

        Route::get('/verify', 'verify')->name('.verify');
    });

Route::get('/loan-calculator', LoanCalculatorController::class)->name('loan-calculator');


/**
 * Authentication Related Routes
 */
Route::name('auth')
    ->controller(AuthController::class)
    ->group(function() {

        Route::post('/register', 'register')->name('.register');
        Route::post('/register-agent', 'registerAgent')->name('.register-agent');

        Route::post('/login', 'login')->name('.login');

        Route::put('/verify-user', 'verifyUser')->name('.verify-user');

        Route::post('/resend-verification-email', 'resendVerificationEmail')->name('.resend-verification-email');

        Route::post('/resend-user-otp', 'resendUserOtp')->name('.resend-user-otp')->middleware('throttle:otp');

        Route::post('/forgot-password', 'forgotPassword')->name('.forgot-password')->middleware('throttle:otp');

        Route::put('/reset-password', 'resetPassword')->name('.reset-password');

        Route::middleware([
                'auth:sanctum',
                'user',
                'user.verified'
            ])
            ->group(function() {

                Route::post('/confirm-bvn', 'confirmBvn')->name('.confirm-bvn');
                Route::post('/confirm-bvn-image', 'confirmBvnImage')->name('.confirm-bvn-image');

                Route::put('/verify-bvn', 'verifyBvn')->name('.verify-bvn');

                Route::post('/resend-bvn-otp', 'resendBvnOtp')->name('.resend-bvn-otp')->middleware('throttle:otp');
            });
    });
/**
 * End of Authentication Related Routes
 */



/**
 * User Major Routes
 */
Route::middleware([
        'auth:sanctum',
        'user',
        'user.verified',
        'bvn.verified'
    ])
    ->group(function() {

        Route::name('applications')
            ->prefix('/applications')
            ->controller(ApplicationController::class)
            ->group(function() {

                Route::get('', 'userIndex')->name('.user-index');
                Route::post('', 'store')->name('.store');
                Route::post('/onboarding-payment', 'onboardingPayment')->name('.onboarding-payment');
                Route::get('/{applicationId}', 'userShow')->name('.user-show');
                Route::post('/{applicationId}/payment', 'payment')->name('.payment');
                Route::get('/{applicationId}/transactions', 'userTransactions')->name('.user-transactions');
                Route::get('/{applicationId}/transactions/{transactionId}', 'userTransaction')->name('.user-transaction');
            });
    });
/**
 * End of User Major Routes
 */



/**
 * Agent Major Routes
 */
Route::middleware([
        'auth:sanctum',
        'agent',
        'email.verified'
    ])
    ->group(function() {

        Route::name('accounts')
            ->prefix('/accounts')
            ->controller(AccountController::class)
            ->group(function() {

                Route::get('', 'userIndex')->name('.user-index');
                Route::post('', 'store')->name('.store');
                Route::get('/{accountId}', 'userShow')->name('.user-show');
            });

        Route::name('balances')
            ->prefix('/balances')
            ->controller(BalanceController::class)
            ->group(function() {

                Route::get('', 'index')->name('.index');
            });
        
        Route::get('/pending-applications', [PendingApplicationsController::class, 'index'])->name('pending-applications.index');
        Route::get('/pending-applications-count', [PendingApplicationsController::class, 'count'])->name('pending-applications.count');

        Route::get('/declined-applications', [DeclinedApplicationsController::class, 'index'])->name('declined-applications.index');
        Route::get('/declined-applications-count', [DeclinedApplicationsController::class, 'count'])->name('declined-applications.count');
        
        Route::get('/incoming-payments', [IncomingPaymentsController::class, 'index'])->name('incoming-payments.index');
        Route::get('/incoming-payments-count', [IncomingPaymentsController::class, 'count'])->name('incoming-payments.count');

        Route::get('/referral-code', ReferralCodeController::class)->name('referral-code');

        Route::get('/referral-list', ReferralListController::class)->name('referral-list');
    });
/**
* End of Agent Major Routes
*/



/**
 * General Related Authentication Routes
 */
Route::name('auth')
    ->middleware(['auth:sanctum'])
    ->group(function() {
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('.logout');

    Route::put('/change-password', [AuthController::class, 'changePassword'])->name('.change-password');
});
/**
 * End of General Related Authentication Routes
 */



/**
 * Administrator Related Routes
 */
Route::name('admin')
    ->prefix('/admin')
    ->group(function() {

        Route::name('.auth')
            ->controller(AuthController::class)
            ->group(function() {

                Route::post('/register', 'adminRegister')->name('.register');

                Route::post('/login', 'adminLogin')->name('.login');
            });

        Route::middleware([
                'auth:sanctum',
                'administrator',
                'email.verified'
            ])
            ->group(function() {

                Route::name('.options')
                    ->prefix('/options')
                    ->controller(OptionController::class)
                    ->group(function() {

                        Route::post('', 'store')->name('.store');
                        Route::put('/{option}', 'update')->name('.update');
                        Route::delete('/{option}', 'destroy')->name('.destroy');
                    });

                Route::name('.tenures')
                    ->prefix('/tenures')
                    ->controller(TenureController::class)
                    ->group(function() {

                        Route::post('', 'store')->name('.store');
                        Route::put('/{tenure}', 'update')->name('.update');
                        Route::delete('/{tenure}', 'destroy')->name('.destroy');
                    });

                Route::name('.applications')
                    ->prefix('/applications')
                    ->controller(ApplicationController::class)
                    ->group(function() {

                        Route::get('', 'index')->name('.index');
                        Route::get('/{application}', 'show')->name('.show');
                        Route::put('/{application}/status', 'status')->name('.status');
                        Route::get('/{application}/passport-details', 'passportDetails')->name('.passport-details');
                        Route::get('/{application}/transactions', 'transactions')->name('.transactions');
                        Route::get('/{application}/transactions/{transaction}', 'transaction')->name('.transaction');
                    });

                Route::get('/users', UserController::class)->name('.users');

                Route::get('/individual-agents', IndividualAgentController::class)->name('.individual-agents');

                Route::get('/agencies', AgencyController::class)->name('.agencies');
            });
    });
/**
 * End of Administrator Related Routes
 */
