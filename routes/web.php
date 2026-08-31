<?php

use App\Http\Controllers\Admin\BuyerController;
use App\Http\Controllers\Admin\FabricRecordController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\StyleController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['role:admin|manager'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [OverviewController::class, 'index'])->name('overview');

        Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
        Route::post('/upload/validate', [UploadController::class, 'validateFile'])->name('upload.validate');
        Route::post('/upload/import', [UploadController::class, 'import'])->name('upload.import');
        Route::get('/upload/template', [UploadController::class, 'template'])->name('upload.template');

        Route::get('/fabric-records-export', [FabricRecordController::class, 'export'])->name('fabric-records.export');
        Route::resource('fabric-records', FabricRecordController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);

        Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('suppliers.toggle');
        Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::patch('/buyers/{buyer}/toggle-active', [BuyerController::class, 'toggleActive'])->name('buyers.toggle');
        Route::resource('buyers', BuyerController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('styles', StyleController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::middleware(['role:admin'])->group(function () {
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        });
    });
});

require __DIR__ . '/auth.php';
