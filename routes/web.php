<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'index'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/summary', [DashboardController::class, 'summary'])->name('api.summary');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/upload', [AdminController::class, 'uploadCsv'])->name('upload');
        Route::get('/smart-upload', [AdminController::class, 'smartUpload'])->name('smart-upload');
        Route::post('/smart-upload', [AdminController::class, 'smartUploadProcess'])->name('smart-upload.process');
        Route::get('/review-extraction', [AdminController::class, 'reviewExtraction'])->name('review-extraction');
        Route::post('/smart-upload/confirm', [AdminController::class, 'smartUploadConfirm'])->name('smart-upload.confirm');
    });
});
