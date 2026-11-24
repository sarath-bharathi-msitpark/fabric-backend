<?php

use App\Http\Controllers\Admin\BuyerController;
use App\Http\Controllers\Admin\Cutting\CuttingAuditController;
use App\Http\Controllers\Admin\Cutting\CuttingBundleController;
use App\Http\Controllers\Admin\Cutting\CuttingChecklistController;
use App\Http\Controllers\Admin\Cutting\CuttingDashboardController;
use App\Http\Controllers\Admin\Cutting\CuttingReportController;
use App\Http\Controllers\Admin\Cutting\CuttingSignoffController;
use App\Http\Controllers\Admin\Cutting\CuttingStageController;
use App\Http\Controllers\Admin\Cutting\CuttingTableController;
use App\Http\Controllers\Admin\FabricRecordController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\Sewing\SewingDashboardController;
use App\Http\Controllers\Admin\Sewing\SewingDefectLogController;
use App\Http\Controllers\Admin\Sewing\SewingDefectMasterController;
use App\Http\Controllers\Admin\Sewing\SewingGridController;
use App\Http\Controllers\Admin\Sewing\SewingLineAssignmentController;
use App\Http\Controllers\Admin\Sewing\SewingLineController;
use App\Http\Controllers\Admin\Sewing\SewingLiveBoardController;
use App\Http\Controllers\Admin\Sewing\SewingMachineController;
use App\Http\Controllers\Admin\Sewing\SewingOperationController;
use App\Http\Controllers\Admin\Sewing\SewingOperatorController;
use App\Http\Controllers\Admin\Sewing\SewingRepeatRedController;
use App\Http\Controllers\Admin\Sewing\SewingReportController;
use App\Http\Controllers\Admin\Sewing\SewingRoundConfigController;
use App\Http\Controllers\Admin\Sewing\SewingRuleController;
use App\Http\Controllers\Admin\Sewing\SewingSheetController;
use App\Http\Controllers\Admin\Sewing\SewingStyleOperationController;
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
        Route::post('/upload/manual', [UploadController::class, 'storeManual'])->name('upload.manual');
        Route::get('/upload/fetch-lot', [UploadController::class, 'fetchLot'])->name('upload.fetch-lot');
        Route::get('/upload/template', [UploadController::class, 'template'])->name('upload.template');

        Route::get('/fabric-records-export', [FabricRecordController::class, 'export'])->name('fabric-records.export');
        Route::get('/fabric-records/{fabric_record}/inspection-report', [FabricRecordController::class, 'inspectionReport'])->name('fabric-records.inspection-report');
        Route::resource('fabric-records', FabricRecordController::class)->only(['index', 'store', 'show', 'edit', 'update', 'destroy']);

        Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('suppliers.toggle');
        Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::patch('/buyers/{buyer}/toggle-active', [BuyerController::class, 'toggleActive'])->name('buyers.toggle');
        Route::resource('buyers', BuyerController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('styles', StyleController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('/styles/import', [StyleController::class, 'import'])->name('styles.import');
        Route::get('/styles/template', [StyleController::class, 'template'])->name('styles.template');

        Route::middleware(['role:admin'])->group(function () {
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        });
    });

    Route::middleware(['role:admin|manager|cutting_qc|cutting_incharge|qam|management|merchandiser'])
        ->prefix('admin/cutting')
        ->name('admin.cutting.')
        ->group(function () {
            Route::get('/', [CuttingDashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/data', [CuttingDashboardController::class, 'data'])->name('dashboard.data');

            Route::get('/audits', [CuttingAuditController::class, 'index'])->name('audits.index');
            Route::get('/audits/export', [CuttingAuditController::class, 'export'])->name('audits.export');
            Route::get('/audits/create', [CuttingAuditController::class, 'create'])->name('audits.create');
            Route::post('/audits', [CuttingAuditController::class, 'store'])->name('audits.store');
            Route::get('/audits/{audit}', [CuttingAuditController::class, 'show'])->name('audits.show');
            Route::get('/audits/{audit}/edit', [CuttingAuditController::class, 'edit'])->name('audits.edit');
            Route::put('/audits/{audit}', [CuttingAuditController::class, 'update'])->name('audits.update');
            Route::delete('/audits/{audit}', [CuttingAuditController::class, 'destroy'])->name('audits.destroy');
            Route::get('/audits/{audit}/report', [CuttingAuditController::class, 'report'])->name('audits.report');

            Route::get('/audits/{audit}/stages/{stage}', [CuttingStageController::class, 'show'])->name('stages.show');
            Route::post('/audits/{audit}/stages/{stage}/draft', [CuttingStageController::class, 'draft'])->name('stages.draft');
            Route::post('/audits/{audit}/stages/{stage}/submit', [CuttingStageController::class, 'submit'])->name('stages.submit');

            Route::post('/audits/{audit}/stages/{stage}/signoff', [CuttingSignoffController::class, 'signoff'])->name('signoffs.store');
            Route::post('/approvals/{approval}/decide', [CuttingSignoffController::class, 'decide'])->name('approvals.decide');
            Route::post('/audits/{audit}/revision', [CuttingSignoffController::class, 'revision'])->name('audits.revision');
            Route::post('/audits/{audit}/release', [CuttingSignoffController::class, 'release'])->name('audits.release');

            Route::get('/bundles/{bundle}/print', [CuttingBundleController::class, 'print'])->name('bundles.print');

            Route::get('/reports', [CuttingReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/{type}', [CuttingReportController::class, 'show'])->name('reports.show');
            Route::get('/reports/{type}/export', [CuttingReportController::class, 'export'])->name('reports.export');

            Route::middleware(['role:admin'])->group(function () {
                Route::get('/checklists', [CuttingChecklistController::class, 'index'])->name('checklists.index');
                Route::put('/checklists/{template}', [CuttingChecklistController::class, 'updateTemplate'])->name('checklists.update');
                Route::post('/checklists/{template}/new-version', [CuttingChecklistController::class, 'newVersion'])->name('checklists.new-version');
                Route::post('/checklists/{template}/checkpoints', [CuttingChecklistController::class, 'storeCheckpoint'])->name('checkpoints.store');
                Route::put('/checkpoints/{checkpoint}', [CuttingChecklistController::class, 'updateCheckpoint'])->name('checkpoints.update');
                Route::delete('/checkpoints/{checkpoint}', [CuttingChecklistController::class, 'destroyCheckpoint'])->name('checkpoints.destroy');
                Route::post('/checkpoints/{checkpoint}/move', [CuttingChecklistController::class, 'moveCheckpoint'])->name('checkpoints.move');
                Route::put('/numbering', [CuttingChecklistController::class, 'updateNumbering'])->name('numbering.update');

                Route::get('/tables', [CuttingTableController::class, 'index'])->name('tables.index');
                Route::post('/tables', [CuttingTableController::class, 'store'])->name('tables.store');
                Route::put('/tables/{table}', [CuttingTableController::class, 'update'])->name('tables.update');
                Route::delete('/tables/{table}', [CuttingTableController::class, 'destroy'])->name('tables.destroy');
            });
        });

    Route::middleware(['role:admin|manager|sewing_qc|sewing_supervisor|production_manager|qam|management|merchandiser'])
        ->prefix('admin/sewing')
        ->name('admin.sewing.')
        ->group(function () {
            Route::get('/', [SewingDashboardController::class, 'index'])->name('dashboard');

            Route::get('/sheets', [SewingSheetController::class, 'index'])->name('sheets.index');
            Route::get('/sheets/create', [SewingSheetController::class, 'create'])->name('sheets.create');
            Route::post('/sheets', [SewingSheetController::class, 'store'])->name('sheets.store');
            Route::get('/sheets/{sheet}', [SewingSheetController::class, 'show'])->name('sheets.show');
            Route::post('/sheets/{sheet}/signoff', [SewingSheetController::class, 'signoff'])->name('sheets.signoff');
            Route::post('/sheets/{sheet}/correct', [SewingSheetController::class, 'correct'])->name('sheets.correct');
            Route::get('/sheets/{sheet}/print', [SewingSheetController::class, 'print'])->name('sheets.print');
            Route::get('/sheets/{sheet}/pdf', [SewingSheetController::class, 'pdf'])->name('sheets.pdf');
            Route::get('/sheets/{sheet}/export', [SewingSheetController::class, 'export'])->name('sheets.export');

            Route::get('/sheets/{sheet}/grid', [SewingGridController::class, 'show'])->name('grid.show');
            Route::post('/sheets/{sheet}/grid/{operation}/result', [SewingGridController::class, 'saveResult'])->name('grid.result');
            Route::post('/sheets/{sheet}/grid/{operation}/defect', [SewingGridController::class, 'logDefect'])->name('grid.defect');
            Route::delete('/sheets/{sheet}/logs/{log}', [SewingGridController::class, 'removeDefect'])->name('grid.defect.remove');
            Route::post('/sheets/{sheet}/results/{result}/reaudit', [SewingGridController::class, 'reAudit'])->name('grid.reaudit');
            Route::post('/sheets/{sheet}/rounds/{round}/close', [SewingGridController::class, 'closeRound'])->name('grid.close-round');

            Route::get('/live-board', [SewingLiveBoardController::class, 'index'])->name('live-board.index');
            Route::get('/live-board/data', [SewingLiveBoardController::class, 'data'])->name('live-board.data');

            Route::get('/defect-log', [SewingDefectLogController::class, 'index'])->name('defect-log.index');
            Route::get('/defect-log/export/{format?}', [SewingDefectLogController::class, 'export'])->name('defect-log.export');
            Route::get('/repeat-red', [SewingRepeatRedController::class, 'index'])->name('repeat-red.index');

            Route::get('/reports', [SewingReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/{type}', [SewingReportController::class, 'show'])->name('reports.show');
            Route::get('/reports/{type}/export', [SewingReportController::class, 'export'])->name('reports.export');

            Route::middleware(['role:admin|qam'])->prefix('settings')->name('settings.')->group(function () {
                Route::resource('lines', SewingLineController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::resource('machines', SewingMachineController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::resource('operators', SewingOperatorController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::resource('operations', SewingOperationController::class)->only(['index', 'store', 'update', 'destroy']);
                Route::get('/line-assignments', [SewingLineAssignmentController::class, 'index'])->name('line-assignments.index');
                Route::post('/line-assignments', [SewingLineAssignmentController::class, 'store'])->name('line-assignments.store');

                Route::get('/style-operations', [SewingStyleOperationController::class, 'index'])->name('style-operations.index');
                Route::post('/style-operations', [SewingStyleOperationController::class, 'store'])->name('style-operations.store');
                Route::post('/style-operations/{style_operation}/move', [SewingStyleOperationController::class, 'move'])->name('style-operations.move');
                Route::delete('/style-operations/{style_operation}', [SewingStyleOperationController::class, 'destroy'])->name('style-operations.destroy');

                Route::get('/defects/template', [SewingDefectMasterController::class, 'template'])->name('defects.template');
                Route::post('/defects/import', [SewingDefectMasterController::class, 'import'])->name('defects.import');
                Route::resource('defects', SewingDefectMasterController::class)->only(['index', 'store', 'update', 'destroy']);

                Route::get('/rules', [SewingRuleController::class, 'index'])->name('rules.index');
                Route::put('/rules', [SewingRuleController::class, 'update'])->name('rules.update');
                Route::get('/rounds', [SewingRoundConfigController::class, 'index'])->name('rounds.index');
                Route::put('/rounds', [SewingRoundConfigController::class, 'update'])->name('rounds.update');
            });
        });
});

require __DIR__.'/auth.php';
