<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
Route::prefix('admin')->name('admin.')->middleware('admin.enabled')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'loginForm'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:30,1,admin-login:');
        Route::middleware('local.registration')->group(function () {
            Route::get('register', [AuthController::class, 'registerForm'])->middleware('throttle:30,1,admin-register-form:')->name('register');
            Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1,admin-register-submit:');
        });
    });
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('{section}', [DashboardController::class, 'placeholder'])->whereIn('section', ['users','content','analytics','settings'])->name('section');
    });
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
});
