<?php

use App\Http\Controllers\Admin\ExtensionController;
use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RentalController;
use Illuminate\Support\Facades\Route;

// Home, send each user to their own start page.
Route::get('/', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    return $user->isAdmin()
        ? redirect()->route('admin.garages.index')
        : redirect()->route('rentals.create');
});

// Login and registration (only for guests)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Tenant features
Route::middleware(['auth', 'role:tenant'])->group(function () {
    Route::get('/rentals/create', [RentalController::class, 'create'])->name('rentals.create');
    Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
});

// Admin features
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/garages', [GarageController::class, 'index'])->name('garages.index');
    Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');

    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('/tenants/{user}/approve', [TenantController::class, 'approve'])->name('tenants.approve');
    Route::post('/tenants/{user}/reject', [TenantController::class, 'reject'])->name('tenants.reject');

    Route::get('/extensions', [ExtensionController::class, 'index'])->name('extensions.index');
    Route::get('/extensions/{rental}', [ExtensionController::class, 'edit'])->name('extensions.edit');
    Route::post('/extensions/{rental}', [ExtensionController::class, 'update'])->name('extensions.update');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});
