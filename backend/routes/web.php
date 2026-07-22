<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MemberQrController;
use App\Http\Controllers\Platform\PlatformLoginController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Account;
use App\Livewire\Activity;
use App\Livewire\Attendance;
use App\Livewire\Billing;
use App\Livewire\Bookings;
use App\Livewire\Classes;
use App\Livewire\GymProfile;
use App\Livewire\Insight;
use App\Livewire\LockDevices;
use App\Livewire\Members;
use App\Livewire\Payments;
use App\Livewire\Plans;
use App\Livewire\Platform\Activity as PlatformActivity;
use App\Livewire\Platform\GymCreate;
use App\Livewire\Platform\Gyms as PlatformGyms;
use App\Livewire\Platform\GymShow;
use App\Livewire\Platform\Overview as PlatformOverview;
use App\Livewire\Platform\SubscriptionPlans;
use App\Livewire\Staff;
use App\Livewire\Trainers;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [LoginController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware(['guest', 'throttle:5,1']);
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth:web')->name('logout');

Route::middleware(['auth:web', 'gym.active'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect(auth()->user()->role === 'owner' ? '/dashboard/plans' : '/dashboard/members');
    });
    Route::get('/dashboard/plans', Plans::class)->name('plans');
    Route::get('/dashboard/members', Members::class)->name('members');
    Route::get('/dashboard/members/{member}/qr', [MemberQrController::class, 'show'])->name('members.qr');
    Route::get('/dashboard/trainers', Trainers::class)->name('trainers');
    Route::get('/dashboard/attendance', Attendance::class)->name('attendance');
    Route::get('/dashboard/classes', Classes::class)->name('classes');
    Route::get('/dashboard/bookings', Bookings::class)->name('bookings');
    Route::get('/dashboard/insight', Insight::class)->name('insight');
    Route::get('/dashboard/staff', Staff::class)->name('staff');
    Route::get('/dashboard/activity', Activity::class)->name('activity');
    Route::get('/dashboard/lock-devices', LockDevices::class)->name('lock-devices');
    Route::get('/dashboard/settings', GymProfile::class)->name('settings');
    Route::get('/dashboard/payments', Payments::class)->name('payments');
    Route::get('/dashboard/payments/{payment}/receipt', [ReceiptController::class, 'show'])->name('payments.receipt');
    Route::get('/dashboard/payments/{payment}/receipt.pdf', [ReceiptController::class, 'pdf'])->name('payments.receipt.pdf');
    Route::get('/dashboard/billing', Billing::class)->name('billing');
    Route::get('/account', Account::class)->name('account');
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// RankSol platform admin panel — separate 'platform' guard, isolated from gym owner/staff auth.
Route::get('/ranksol/login', [PlatformLoginController::class, 'create'])->middleware('guest:platform')->name('platform.login');
Route::post('/ranksol/login', [PlatformLoginController::class, 'store'])->middleware(['guest:platform', 'throttle:5,1']);
Route::post('/ranksol/logout', [PlatformLoginController::class, 'destroy'])->middleware('auth:platform')->name('platform.logout');

Route::middleware('auth:platform')->group(function () {
    Route::get('/ranksol', PlatformOverview::class)->name('platform.overview');
    Route::get('/ranksol/gyms/new', GymCreate::class)->name('platform.gyms.new');
    Route::get('/ranksol/gyms/{gym}', GymShow::class)->name('platform.gyms.show');
    Route::get('/ranksol/gyms', PlatformGyms::class)->name('platform.gyms');
    Route::get('/ranksol/plans', SubscriptionPlans::class)->name('platform.plans');
    Route::get('/ranksol/activity', PlatformActivity::class)->name('platform.activity');
});
