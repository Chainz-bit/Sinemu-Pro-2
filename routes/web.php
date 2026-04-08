<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\RegisteredUserController as AdminRegisteredUserController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::middleware('auth')->get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('register', [AdminRegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [AdminRegisteredUserController::class, 'store']);

        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login']);
    });
    

    Route::middleware('admin')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    });
});

require __DIR__.'/auth.php';
