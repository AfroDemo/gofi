<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackageIndexController;
use App\Http\Controllers\PackageManagementController;
use App\Http\Controllers\TransactionIndexController;
use App\Http\Controllers\VoucherIndexController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('packages', PackageIndexController::class)->name('packages.index');
    Route::get('packages/create', [PackageManagementController::class, 'create'])->name('packages.create');
    Route::post('packages', [PackageManagementController::class, 'store'])->name('packages.store');
    Route::get('packages/{package}/edit', [PackageManagementController::class, 'edit'])->name('packages.edit');
    Route::patch('packages/{package}', [PackageManagementController::class, 'update'])->name('packages.update');
    Route::get('vouchers', VoucherIndexController::class)->name('vouchers.index');
    Route::get('transactions', TransactionIndexController::class)->name('transactions.index');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
