<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\InventoryAdjustmentController;
use App\Http\Controllers\Admin\MasterListController;
use App\Http\Controllers\Admin\ProductConfigurationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Admin Dashboard (Protected Route)
Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'admin'])
    ->name('admin.dashboard');

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::get('/profile/avatar', [AccountController::class, 'avatar'])->name('profile.avatar');
        Route::get('/settings', [AccountController::class, 'settings'])->name('settings');
        Route::get('/products', [MasterListController::class, 'products'])->name('products.index');
        Route::post('/products', [MasterListController::class, 'storeProduct'])->name('products.store');
        Route::patch('/products/bulk-update', [MasterListController::class, 'bulkUpdateProducts'])->name('products.bulk-update');
        Route::delete('/products/{product}', [MasterListController::class, 'destroyProduct'])->name('products.destroy');
        Route::get('/products/{product}', [MasterListController::class, 'product'])->name('products.show');
        Route::get('/suppliers', [MasterListController::class, 'suppliers'])->name('suppliers.index');
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::patch('/customers/bulk-update', [CustomerController::class, 'bulkUpdate'])->name('customers.bulk-update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/inventory/adjustment', [InventoryAdjustmentController::class, 'index'])->name('inventory.adjustment');
        Route::get('/inventory/adjustment/data', [InventoryAdjustmentController::class, 'data'])->name('inventory.adjustment.data');
        Route::post('/inventory/adjustment/save', [InventoryAdjustmentController::class, 'save'])->name('inventory.adjustment.save');
        Route::get('/system-security/user-management', [UserManagementController::class, 'index'])->name('system-security.user-management');
        Route::get('/system-security/user-management/fetch/{role}', [UserManagementController::class, 'fetch'])->name('system-security.user-management.fetch');
        Route::post('/system-security/user-management', [UserManagementController::class, 'store'])->name('system-security.user-management.store');
        Route::put('/system-security/user-management/{login}', [UserManagementController::class, 'update'])->name('system-security.user-management.update');
        Route::delete('/system-security/user-management/{login}', [UserManagementController::class, 'destroy'])->name('system-security.user-management.destroy');
        Route::get('/product-configuration', [ProductConfigurationController::class, 'index'])->name('product-configuration.index');
        Route::get('/product-configuration/sources', [ProductConfigurationController::class, 'listSources'])->name('product-configuration.sources');
        Route::post('/product-configuration/sources', [ProductConfigurationController::class, 'storeSource'])->name('product-configuration.sources.store');
        Route::put('/product-configuration/sources/{source}', [ProductConfigurationController::class, 'updateSource'])->name('product-configuration.sources.update');
        Route::delete('/product-configuration/sources/{source}', [ProductConfigurationController::class, 'destroySource'])->name('product-configuration.sources.destroy');
        Route::post('/product-configuration/rate/refresh', [ProductConfigurationController::class, 'refreshRate'])->name('product-configuration.rate.refresh');
        Route::post('/product-configuration/equivalencies', [ProductConfigurationController::class, 'storeEquivalency'])->name('product-configuration.equivalencies.store');
        Route::get('/product-configuration/logs', [ProductConfigurationController::class, 'listLogs'])->name('product-configuration.logs');
        Route::get('/system-security/archive', function () {
            return view('admin.system-security.archive');
        })->name('system-security.archive');
        Route::get('/system-security/audit-trail', function () {
            return view('admin.system-security.audit-trail');
        })->name('system-security.audit-trail');
        Route::get('/system-security/data-sync', function () {
            return view('admin.system-security.data-sync');
        })->name('system-security.data-sync');
    });

Route::redirect('/dashboard', '/admin/dashboard')
    ->middleware(['auth', 'admin'])
    ->name('dashboard');
