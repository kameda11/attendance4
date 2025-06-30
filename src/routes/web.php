<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;

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

// 管理者認証関連のルート
Route::get('/admin/login', [AdminController::class, 'showLoginForm'])->name('admin.login.form');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

// 管理者認証が必要なルート
Route::middleware(['admin.auth'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/requests', [AdminController::class, 'requests'])->name('admin.requests');
    Route::get('/admin/attendances', [AdminController::class, 'attendances'])->name('admin.attendances');
    Route::get('/admin/attendance/detail/{id}', [AdminController::class, 'attendanceDetail'])->name('admin.attendance.detail');
    Route::put('/admin/attendance/update/{id}', [AdminController::class, 'attendanceUpdate'])->name('admin.attendance.update');
    Route::post('/admin/attendance/store', [AdminController::class, 'attendanceStore'])->name('admin.attendance.store');

    // 申請関連
    Route::get('/admin/attendance/requests', [AdminController::class, 'attendanceRequests'])->name('admin.attendance.requests');
    Route::get('/admin/attendance/request/detail/{id}', [AdminController::class, 'attendanceRequestDetail'])->name('admin.attendance.request.detail');
    Route::get('/admin/attendance/request/approval/{id}', [AdminController::class, 'showApprovalPage'])->name('admin.attendance.request.approval');
    Route::post('/admin/attendance/request/approve/{id}', [AdminController::class, 'approveRequest'])->name('admin.attendance.request.approve');

    // スタッフ別勤怠一覧
    Route::get('/admin/user/{userId}/attendance/list', [AdminController::class, 'userAttendanceList'])->name('admin.user.attendance.list');
});

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
    Route::post('/attendance/store', [UserController::class, 'attendanceStore'])->name('user.attendance.store');

    // 申請関連
    Route::get('/attendance/requests', [UserController::class, 'attendanceRequests'])->name('user.attendance.requests');
    Route::get('/stamp_correction_request/list', [UserController::class, 'stampCorrectionRequests'])->name('user.stamp.correction.requests');
});
