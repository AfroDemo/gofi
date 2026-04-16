<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackageIndexController;
use App\Http\Controllers\PackageManagementController;
use App\Http\Controllers\TransactionIndexController;
use App\Http\Controllers\TransactionShowController;
use App\Http\Controllers\VoucherBatchController;
use App\Http\Controllers\VoucherIndexController;
use App\Http\Controllers\VoucherProfileManagementController;
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
    Route::get('voucher-profiles/create', [VoucherProfileManagementController::class, 'create'])->name('voucher-profiles.create');
    Route::post('voucher-profiles', [VoucherProfileManagementController::class, 'store'])->name('voucher-profiles.store');
    Route::get('voucher-profiles/{voucherProfile}/edit', [VoucherProfileManagementController::class, 'edit'])->name('voucher-profiles.edit');
    Route::patch('voucher-profiles/{voucherProfile}', [VoucherProfileManagementController::class, 'update'])->name('voucher-profiles.update');
    Route::get('voucher-profiles/{voucherProfile}/generate', [VoucherBatchController::class, 'create'])->name('voucher-batches.create');
    Route::post('voucher-profiles/{voucherProfile}/generate', [VoucherBatchController::class, 'store'])->name('voucher-batches.store');
    Route::get('transactions', TransactionIndexController::class)->name('transactions.index');
    Route::get('transactions/{transaction}', TransactionShowController::class)->name('transactions.show');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
