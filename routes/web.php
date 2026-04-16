<?php

use App\Http\Controllers\BranchIndexController;
use App\Http\Controllers\BranchManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackageIndexController;
use App\Http\Controllers\PackageManagementController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\TenantIndexController;
use App\Http\Controllers\TenantManagementController;
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

Route::prefix('portal/{tenantSlug}/{branchCode}')->group(function () {
    Route::get('/', [PortalController::class, 'show'])->name('portal.show');
    Route::post('/checkout', [PortalController::class, 'checkout'])->name('portal.checkout');
    Route::post('/voucher', [PortalController::class, 'redeemVoucher'])->name('portal.voucher.redeem');
    Route::get('/transactions/{reference}', [PortalController::class, 'showTransaction'])->name('portal.transactions.show');
    Route::post('/transactions/{reference}/refresh', [PortalController::class, 'refreshTransactionStatus'])->name('portal.transactions.refresh');
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('tenants', TenantIndexController::class)->name('tenants.index');
    Route::get('tenants/create', [TenantManagementController::class, 'create'])->name('tenants.create');
    Route::post('tenants', [TenantManagementController::class, 'store'])->name('tenants.store');
    Route::get('tenants/{tenant}/edit', [TenantManagementController::class, 'edit'])->name('tenants.edit');
    Route::patch('tenants/{tenant}', [TenantManagementController::class, 'update'])->name('tenants.update');
    Route::get('branches', BranchIndexController::class)->name('branches.index');
    Route::get('branches/create', [BranchManagementController::class, 'create'])->name('branches.create');
    Route::post('branches', [BranchManagementController::class, 'store'])->name('branches.store');
    Route::get('branches/{branch}/edit', [BranchManagementController::class, 'edit'])->name('branches.edit');
    Route::patch('branches/{branch}', [BranchManagementController::class, 'update'])->name('branches.update');
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
