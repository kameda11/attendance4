<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 認証関連のルート
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [LoginController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [LoginController::class, 'register'])->name('register');

// メール認証関連のルート
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [LoginController::class, 'showVerificationNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [LoginController::class, 'verify'])
        ->middleware(['signed'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [LoginController::class, 'resendVerificationEmail'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
});

// 認証・メール認証が必要なルート
Route::middleware(['auth', 'verified'])->group(function () {
    // メインページ（勤務管理）
    Route::get('/', [UserController::class, 'attendance'])->name('user.dashboard');

    // 勤務管理
    Route::get('/attendance', [UserController::class, 'attendance'])->name('user.attendance');
    Route::post('/attendance/clock-in', [UserController::class, 'clockIn'])->name('user.clock-in');
    Route::post('/attendance/clock-out', [UserController::class, 'clockOut'])->name('user.clock-out');
    Route::post('/attendance/break-start', [UserController::class, 'breakStart'])->name('user.break-start');
    Route::post('/attendance/break-end', [UserController::class, 'breakEnd'])->name('user.break-end');

    // 勤務履歴
    Route::get('/attendance/history', [UserController::class, 'attendanceHistory'])->name('user.attendance-history');
    Route::get('/attendance/list', [UserController::class, 'attendanceList'])->name('user.attendance.list');
    Route::get('/attendance/detail/{id}', [UserController::class, 'attendanceDetail'])->name('user.attendance.detail');
    Route::put('/attendance/update/{id}', [UserController::class, 'attendanceUpdate'])->name('user.attendance.update');
});
