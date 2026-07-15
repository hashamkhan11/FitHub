<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MemberQrController;
use App\Livewire\Attendance;
use App\Livewire\Bookings;
use App\Livewire\Classes;
use App\Livewire\Members;
use App\Livewire\Plans;
use App\Livewire\Progress;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [LoginController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth:web')->name('logout');

Route::middleware('auth:web')->group(function () {
    Route::redirect('/dashboard', '/dashboard/plans');
    Route::get('/dashboard/plans', Plans::class)->name('plans');
    Route::get('/dashboard/members', Members::class)->name('members');
    Route::get('/dashboard/members/{member}/qr', [MemberQrController::class, 'show'])->name('members.qr');
    Route::get('/dashboard/attendance', Attendance::class)->name('attendance');
    Route::get('/dashboard/classes', Classes::class)->name('classes');
    Route::get('/dashboard/bookings', Bookings::class)->name('bookings');
    Route::get('/dashboard/progress', Progress::class)->name('progress');
});
