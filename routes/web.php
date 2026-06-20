<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\TenantApplicationController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TenantAccessController;
use App\Http\Controllers\Tenant\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'superadmin'])->name('dashboard');
    Route::resource('applications', ApplicationController::class);
    Route::resource('tenants', TenantController::class);
    Route::resource('tenants.licenses', TenantApplicationController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::post('tenants/{tenant}/licenses/{license}/regenerate-secret', [TenantApplicationController::class, 'regenerateSecret'])
        ->name('tenants.licenses.regenerate-secret');
    Route::post('tenants/{tenant}/licenses/{license}/test-connection', [TenantApplicationController::class, 'testConnection'])
        ->name('tenants.licenses.test-connection');
    Route::get('tenants/{tenant}/licenses/{license}/debug-fetch/{type}/{period}', [TenantApplicationController::class, 'debugFetch'])
        ->name('tenants.licenses.debug-fetch');
    Route::resource('users', UserController::class);
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/{log}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
});

Route::middleware(['auth', 'role:tenant_owner,tenant_staff'])->prefix('app')->name('tenant.')->group(function () {
    Route::get('/', [DashboardController::class, 'tenant'])->name('home');
    Route::get('/access/{license}', [TenantAccessController::class, 'redirect'])->name('access');
    Route::get('/reports', [\App\Http\Controllers\Tenant\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{type}', [\App\Http\Controllers\Tenant\ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{type}/csv', [\App\Http\Controllers\Tenant\ReportController::class, 'exportCsv'])->name('reports.csv');
    Route::get('/reports/{type}/pdf', [\App\Http\Controllers\Tenant\ReportController::class, 'exportPdf'])->name('reports.pdf');
});

Route::middleware(['auth', 'role:tenant_owner'])->prefix('app/users')->name('tenant.staff.')->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('index');
    Route::get('/create', [StaffController::class, 'create'])->name('create');
    Route::post('/', [StaffController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [StaffController::class, 'edit'])->name('edit');
    Route::put('/{user}', [StaffController::class, 'update'])->name('update');
    Route::delete('/{user}', [StaffController::class, 'destroy'])->name('destroy');
});
