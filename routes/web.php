<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetTypeController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssignmentTemplateController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ActaController;
use App\Http\Controllers\AssetTransitionController;
use App\Http\Controllers\DeletionRequestController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\OtroAssetAssignmentController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ActaExcelTemplateController;
use App\Http\Controllers\ActaExcelTemplateFieldController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\OtroLoanController;
use App\Http\Controllers\AxiController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\SecurityController;

/*
|--------------------------------------------------------------------------
| RUTA PUBLICA
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| FIRMAS PÚBLICAS (sin autenticación — token en URL)
|--------------------------------------------------------------------------
*/
Route::get('/sign/{token}',  [ActaController::class, 'signPage'])   ->name('sign.acta');
Route::post('/sign/{token}', [ActaController::class, 'submitSign']) ->name('sign.acta.submit');

/*
|--------------------------------------------------------------------------
| AUTENTICACION 
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN DE DOS FACTORES (2FA)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('2fa')->name('2fa.')->group(function () {
    Route::get('/setup',   [TwoFactorController::class, 'setup'])        ->name('setup');
    Route::post('/setup',  [TwoFactorController::class, 'confirm'])      ->name('confirm')       ->middleware('throttle:10,1');
    Route::get('/verify',  [TwoFactorController::class, 'verify'])       ->name('verify');
    Route::post('/verify', [TwoFactorController::class, 'validateCode']) ->name('validate')      ->middleware('throttle:10,1');
    Route::post('/disable',[TwoFactorController::class, 'disable'])      ->name('disable')       ->middleware('throttle:5,1');
});

/*
|--------------------------------------------------------------------------
| CONSENTIMIENTO DE DATOS — Ley 1581/2012 Colombia
| Solo requiere auth (el usuario aún puede no haber aceptado la política)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('consent')->name('consent.')->group(function () {
    Route::get('/',       [\App\Http\Controllers\ConsentController::class, 'show'])    ->name('show');
    Route::post('/accept',[\App\Http\Controllers\ConsentController::class, 'accept'])  ->name('accept');
    Route::get('/history',[\App\Http\Controllers\ConsentController::class, 'history']) ->name('history');
});

/*
|--------------------------------------------------------------------------
| CENTRO DE SEGURIDAD
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'require.2fa'])->prefix('security')->name('security.')->group(function () {
    Route::get('/',                      [SecurityController::class, 'index'])                  ->name('index');
    Route::delete('/sessions/{id}',      [SecurityController::class, 'revokeSession'])          ->name('sessions.revoke');
    Route::post('/sessions/revoke-all',  [SecurityController::class, 'revokeAllOtherSessions']) ->name('sessions.revoke-all');
    Route::post('/2fa/recovery-codes',   [SecurityController::class, 'regenerateRecoveryCodes'])->name('2fa.recovery-codes');
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'require.consent', 'require.2fa'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [AdminController::class, 'dashboard'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | PERFIL
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])
        ->name('profile.signature.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | COLABORADORES
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:collaborators.view')->group(function () {

        Route::get('/collaborators', [CollaboratorController::class, 'index'])
            ->name('collaborators.index');

        Route::get('/collaborators/create', [CollaboratorController::class, 'create'])
            ->name('collaborators.create')
            ->middleware('can:collaborators.create');

        Route::post('/collaborators', [CollaboratorController::class, 'store'])
            ->name('collaborators.store')
            ->middleware('can:collaborators.create');

        Route::get('/collaborators/{collaborator}', [CollaboratorController::class, 'show'])
            ->name('collaborators.show');

        Route::get('/collaborators/{collaborator}/edit', [CollaboratorController::class, 'edit'])
            ->name('collaborators.edit')
            ->middleware('can:collaborators.edit');

        Route::put('/collaborators/{collaborator}', [CollaboratorController::class, 'update'])
            ->name('collaborators.update')
            ->middleware('can:collaborators.edit');
    });

    /*
    |--------------------------------------------------------------------------
    | TIPOS DE ACTIVOS (TI / OTROS)
    |--------------------------------------------------------------------------
    */
    Route::prefix('asset-types')
        ->name('asset-types.')
        ->group(function () {

            // Listado por categoria (TI / OTRO)
            Route::get('/{category}', [AssetTypeController::class, 'index'])
                ->name('index')
                ->middleware('can:asset-types.view');

            // Crear tipo
            Route::get('/{category}/create', [AssetTypeController::class, 'create'])
                ->name('create')
                ->middleware('can:asset-types.create');

            // Guardar tipo
            Route::post('/', [AssetTypeController::class, 'store'])
                ->name('store')
                ->middleware('can:asset-types.create');

                  // Editar tipo (formulario)
            Route::get('/{assetType}/edit', [AssetTypeController::class, 'edit'])
                ->name('edit')
                ->middleware('can:asset-types.edit');
        
            // Actualizar tipo
            Route::put('/{assetType}', [AssetTypeController::class, 'update'])
                ->name('update')
                ->middleware('can:asset-types.edit');
            
            // Eliminar tipo
            Route::delete('/{assetType}', [AssetTypeController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:asset-types.delete');
    });
   

    /*
    |--------------------------------------------------------------------------
    | TECNOLOGIA (TI)
    |--------------------------------------------------------------------------
    */
    Route::prefix('tech')
        ->name('tech.')
        ->group(function () {

            // ======================
            // HUBS DE MÓDULO TI
            // ======================
            Route::get('/assets/hub', fn() => view('tech.assets.hub'))
                ->name('assets.hub')
                ->middleware('can:tech.assets.view');

            Route::get('/assignments/hub', fn() => view('tech.assignments.hub'))
                ->name('assignments.hub')
                ->middleware('can:tech.assets.assign');

            Route::get('/loans/hub', fn() => view('tech.loans.hub'))
                ->name('loans.hub')
                ->middleware('can:tech.assets.view');

            Route::get('/loans',               [LoanController::class, 'index'])         ->name('loans.index')        ->middleware('can:tech.assets.view');
            Route::get('/loans/export',         [LoanController::class, 'export'])        ->name('loans.export')       ->middleware('can:tech.assets.view');
            Route::get('/loans/create',         [LoanController::class, 'create'])        ->name('loans.create')       ->middleware('can:tech.assets.assign');
            Route::post('/loans',               [LoanController::class, 'store'])         ->name('loans.store')        ->middleware('can:tech.assets.assign');
            Route::get('/loans/{loan}',         [LoanController::class, 'show'])          ->name('loans.show')         ->middleware('can:tech.assets.view');
            Route::get('/loans/{loan}/return',  [LoanController::class, 'returnForm'])    ->name('loans.return')       ->middleware('can:tech.assets.assign');
            Route::post('/loans/{loan}/return', [LoanController::class, 'processReturn']) ->name('loans.return.store') ->middleware('can:tech.assets.assign');

            Route::get('/disposals/hub', fn() => view('tech.disposals.hub'))
                ->name('disposals.hub')
                ->middleware('can:tech.assets.disposal.view');

            Route::get('/disposals',        [DeletionRequestController::class, 'techIndex'])
                ->name('disposals.index')
                ->middleware('can:tech.assets.disposal.view');

            Route::get('/disposals/create', [DeletionRequestController::class, 'techCreate'])
                ->name('disposals.create')
                ->middleware('can:tech.assets.disposal.request');

            Route::get('/reports/hub', [ReportController::class, 'tech'])
                ->name('reports.hub')
                ->middleware('can:tech.reports.view');

            // ======================
            // ACTIVOS TI
            // ======================
            Route::get('/assets', [AssetController::class, 'index'])
                ->name('assets.index')
                ->middleware('can:tech.assets.view');

            Route::get('/assets/create', [AssetController::class, 'create'])
                ->name('assets.create')
                ->middleware('can:tech.assets.create');

            Route::post('/assets', [AssetController::class, 'store'])
                ->name('assets.store')
                ->middleware('can:tech.assets.create');

            Route::get('/assets/history', [AssetController::class, 'techAssetsHistory'])
                ->name('assets.history.index')
                ->middleware('can:tech.history.view');

            Route::get('/assets/{asset}', [AssetController::class, 'show'])
                ->name('assets.show')
                ->middleware('can:tech.assets.view');

            Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])
                ->name('assets.edit')
                ->middleware('can:tech.assets.edit');

            Route::put('/assets/{asset}', [AssetController::class, 'update'])
                ->name('assets.update')
                ->middleware('can:tech.assets.edit');

            // ── ASIGNACIONES TI ────────────────────────────────────────
            Route::get('/assignments', [AssignmentController::class, 'index'])
                ->name('assignments.index')
                ->middleware('can:tech.assets.assign');

            Route::get('/assignments/search/collaborators', [AssignmentController::class, 'search'])
                ->name('assignments.search')
                ->middleware('can:collaborators.view');

            Route::get('/assignments/create', [AssignmentController::class, 'create'])
                ->name('assignments.create')
                ->middleware('can:tech.assets.assign');

            Route::post('/assignments', [AssignmentController::class, 'store'])
                ->name('assignments.store')
                ->middleware('can:tech.assets.assign');

            Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])
                ->name('assignments.show')
                ->middleware('can:tech.assets.assign');

            Route::get('/assignments/{assignment}/return', [AssignmentController::class, 'returnForm'])
                ->name('assignments.return')
                ->middleware('can:tech.assets.assign');

            Route::post('/assignments/{assignment}/return', [AssignmentController::class, 'processReturn'])
                ->name('assignments.return.store')
                ->middleware('can:tech.assets.assign');

            Route::get('/assignments/collaborator/{collaborator}/assets', [AssignmentController::class, 'collaboratorAssets'])
                ->name('assignments.collaborator.assets')
                ->middleware('can:tech.assets.assign');

            // ── HISTORIAL TI ───────────────────────────────────────────
            Route::get('/history', [AssignmentController::class, 'history'])
                ->name('history.index')
                ->middleware('can:tech.history.view');

            // ======================
            // REPORTES TI
            // ======================
            Route::get('/reports', [ReportController::class, 'tech'])
                ->name('reports.index')
                ->middleware('can:tech.reports.view');

            Route::get('/reports/export', [ReportController::class, 'techExport'])
                ->name('reports.export')
                ->middleware('can:tech.reports.view');

            Route::get('/reports/niif-export', [ReportController::class, 'techNiifExport'])
                ->name('reports.niif-export')
                ->middleware('can:tech.reports.view');
        });

    /*
    |--------------------------------------------------------------------------
    | OTROS ACTIVOS
    |--------------------------------------------------------------------------
    */
    Route::prefix('assets')
        ->name('assets.')
        ->group(function () {

            // ── HUBS (deben ir ANTES de las rutas con parámetros) ──────────────
            Route::get('/hub',              fn() => view('assets.hub'))               ->name('hub')              ->middleware('can:assets.view');
            Route::get('/assignments/hub',  fn() => view('assets.assignments.hub'))   ->name('assignments.hub')  ->middleware('can:assets.assign');
            Route::get('/disposals/hub',    fn() => view('assets.disposals.hub'))     ->name('disposals.hub')    ->middleware('can:assets.disposal.view');
            Route::get('/disposals',        [DeletionRequestController::class, 'assetsIndex'])  ->name('disposals.index')  ->middleware('can:assets.disposal.view');
            Route::get('/disposals/create', [DeletionRequestController::class, 'assetsCreate']) ->name('disposals.create') ->middleware('can:assets.disposal.request');
            Route::get('/reports/hub',      [ReportController::class, 'assets'])        ->name('reports.hub')      ->middleware('can:assets.reports.view');

            // ── INVENTARIO DE OTROS ACTIVOS — Rutas estáticas ─────────────────
            Route::middleware('can:assets.view')->group(function () {
                Route::get('/',       [AssetController::class, 'assetsIndex'])  ->name('index');
                Route::get('/create', [AssetController::class, 'assetsCreate']) ->name('create')
                    ->middleware('can:assets.create');
                Route::post('/',      [AssetController::class, 'assetsStore'])  ->name('store')
                    ->middleware('can:assets.create');
                // NOTA: /{asset} va AL FINAL del grupo para no capturar rutas específicas
            });

            // ── ASIGNACIONES DE OTROS ACTIVOS — CRUD ──────────────────────────
            Route::middleware('can:assets.assign')->group(function () {
                Route::get('/assignments',                          [OtroAssetAssignmentController::class, 'index'])        ->name('assignments.index');
                Route::get('/assignments/create',                   [OtroAssetAssignmentController::class, 'create'])       ->name('assignments.create');
                Route::post('/assignments',                         [OtroAssetAssignmentController::class, 'store'])        ->name('assignments.store');
                Route::get('/assignments/{assignment}',             [OtroAssetAssignmentController::class, 'show'])         ->name('assignments.show');
                Route::get('/assignments/{assignment}/return',      [OtroAssetAssignmentController::class, 'returnAssets']) ->name('assignments.return');
                Route::post('/assignments/{assignment}/return',     [OtroAssetAssignmentController::class, 'processReturn'])->name('assignments.return.process');
            });

            // ── PRÉSTAMOS OTROS ACTIVOS ───────────────────────────────────────────
            Route::get('/loans/hub',           fn() => view('assets.loans.hub'))                            ->name('loans.hub')          ->middleware('can:assets.view');
            Route::get('/loans',               [OtroLoanController::class, 'index'])                        ->name('loans.index')        ->middleware('can:assets.view');
            Route::get('/loans/export',        [OtroLoanController::class, 'export'])                       ->name('loans.export')       ->middleware('can:assets.view');
            Route::get('/loans/create',        [OtroLoanController::class, 'create'])                       ->name('loans.create')       ->middleware('can:assets.assign');
            Route::post('/loans',              [OtroLoanController::class, 'store'])                        ->name('loans.store')        ->middleware('can:assets.assign');
            Route::get('/loans/{loan}',        [OtroLoanController::class, 'show'])                         ->name('loans.show')         ->middleware('can:assets.view');
            Route::get('/loans/{loan}/return', [OtroLoanController::class, 'returnForm'])                   ->name('loans.return')       ->middleware('can:assets.assign');
            Route::post('/loans/{loan}/return',[OtroLoanController::class, 'processReturn'])                ->name('loans.return.store') ->middleware('can:assets.assign');

            // ── HISTORIAL Y REPORTES ────────────────────────────────────────────
            Route::get('/history', [AssetController::class, 'assetsHistory'])
                ->name('history.index')
                ->middleware('can:assets.history.view');

            Route::get('/reports', [ReportController::class, 'assets'])
                ->name('reports.index')
                ->middleware('can:assets.reports.view');

            Route::get('/reports/export', [ReportController::class, 'assetsExport'])
                ->name('reports.export')
                ->middleware('can:assets.reports.view');

            Route::get('/reports/niif-export', [ReportController::class, 'assetsNiifExport'])
                ->name('reports.niif-export')
                ->middleware('can:assets.reports.view');

            // ── INVENTARIO — Rutas con parámetro (SIEMPRE AL FINAL del grupo) ──
            // Deben ir después de todas las rutas estáticas para evitar colisiones.
            Route::middleware('can:assets.view')->group(function () {
                Route::get('/{asset}',      [AssetController::class, 'assetsShow'])   ->name('show');
                Route::get('/{asset}/edit', [AssetController::class, 'assetsEdit'])   ->name('edit')
                    ->middleware('can:assets.edit');
                Route::put('/{asset}',      [AssetController::class, 'assetsUpdate']) ->name('update')
                    ->middleware('can:assets.edit');
            });
        });

    // Áreas (espacios físicos para asignación de Otros Activos)
    Route::prefix('areas')->name('areas.')->middleware(['auth', 'can:assets.assign'])->group(function () {
        Route::get('/',              [AreaController::class, 'index'])  ->name('index');
        Route::get('/create',        [AreaController::class, 'create']) ->name('create');
        Route::post('/',             [AreaController::class, 'store'])  ->name('store');
        Route::get('/{area}/edit',   [AreaController::class, 'edit'])   ->name('edit');
        Route::put('/{area}',        [AreaController::class, 'update']) ->name('update');
    });

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRACIÓN — HUBS
    |--------------------------------------------------------------------------
    */
    Route::get('/admin/hub',       fn() => view('admin.hub'))       ->name('admin.hub')       ->middleware('can:users.manage');
    Route::get('/admin/permissions',  [PermissionController::class, 'index'])  ->name('admin.permissions.index')  ->middleware('can:users.manage');
    Route::post('/admin/permissions', [PermissionController::class, 'update']) ->name('admin.permissions.update') ->middleware('can:users.manage');
    Route::get('/audit/hub',  [AuditController::class, 'hub'])    ->name('audit.hub')    ->middleware('can:audit.view');
    Route::get('/audit/export', [AuditController::class, 'export'])->name('audit.export')->middleware('can:audit.export');
    Route::get('/documents/hub',   fn() => view('documents.hub'))   ->name('documents.hub')   ->middleware('auth');

    /*
    |--------------------------------------------------------------------------
    | ACTAS DIGITALES
    |--------------------------------------------------------------------------
    */
    Route::prefix('actas')->name('actas.')->middleware('auth')->group(function () {

        Route::get('/',                           [ActaController::class, 'index'])       ->name('index');
        Route::get('/{acta}',                     [ActaController::class, 'show'])        ->name('show');
        Route::get('/{acta}/pdf',                 [ActaController::class, 'downloadPdf']) ->name('pdf');
        Route::get('/{acta}/preview',             [ActaController::class, 'previewPdf'])  ->name('preview');
        // Excel (plantilla configurable)
        Route::post('/{acta}/excel/draft',        [ActaController::class, 'generateExcelDraft'])->name('excel.draft.generate');
        Route::get('/{acta}/excel/draft',         [ActaController::class, 'downloadExcelDraft'])->name('excel.draft.download');
        Route::post('/{acta}/excel/final',        [ActaController::class, 'uploadExcelFinal'])->name('excel.final.upload');
        Route::get('/{acta}/excel/final',         [ActaController::class, 'downloadExcelFinal'])->name('excel.final.download');
        Route::post('/{acta}/fields',             [ActaController::class, 'updateWebFields'])->name('fields.update');
        Route::post('/{acta}/pdf/final',          [ActaController::class, 'generatePdfFinal'])->name('pdf.final.generate');
        Route::post('/{acta}/send',               [ActaController::class, 'send'])        ->name('send');
        Route::post('/{acta}/sign',               [ActaController::class, 'signInternal'])->name('sign.internal');
        Route::patch('/{acta}/void',              [ActaController::class, 'void'])        ->name('void');
        Route::post('/generate/{assignment}',     [ActaController::class, 'generate'])    ->name('generate');
    });
    Route::prefix('ai')->name('ai.')->middleware('auth')->group(function () {
        Route::get('/hub',                                 [AxiController::class, 'hub'])                ->name('hub');
        Route::post('/new',                                [AxiController::class, 'newConversation'])    ->name('new');
        Route::post('/chat',                               [AxiController::class, 'chat'])               ->name('chat');
        Route::delete('/conversations/{conversation}',     [AxiController::class, 'deleteConversation']) ->name('delete');
        Route::get('/conversations/{conversation}/export', [AxiController::class, 'exportConversation']) ->name('export');
    });

    /*
    |--------------------------------------------------------------------------
    | SOLICITUDES DE BAJA (deletion requests)
    |--------------------------------------------------------------------------
    */
    // Solicitar baja (cualquier usuario con permiso de gestión de activos)
    Route::post('/assets/{asset}/deletion-request', [DeletionRequestController::class, 'store'])
        ->name('deletion-requests.store')
        ->middleware('can:tech.assets.assign');

    // Gestión de solicitudes (solo Aprobador)
    Route::prefix('admin/deletion-requests')
        ->name('deletion-requests.')
        ->middleware(['can:assets.approve.deletion', 'throttle:30,1'])
        ->group(function () {
            Route::get('/',                                    [DeletionRequestController::class, 'index'])   ->name('index');
            Route::post('/{deletionRequest}/approve',          [DeletionRequestController::class, 'approve']) ->name('approve');
            Route::post('/{deletionRequest}/reject',           [DeletionRequestController::class, 'reject'])  ->name('reject');
        });

    /*
    |--------------------------------------------------------------------------
    | TRANSICIONES DE ACTIVO (cambios de estado)
    |--------------------------------------------------------------------------
    */
    Route::prefix('assets/{asset}/transition')
        ->name('asset.transition.')
        ->group(function () {
            // Movimientos operativos — requieren permiso de asignación
            Route::middleware('permission:tech.assets.assign|assets.assign')->group(function () {
                Route::post('/retire',      [AssetTransitionController::class, 'retire'])         ->name('retire');
                Route::post('/maintenance', [AssetTransitionController::class, 'toMaintenance'])  ->name('maintenance');
                Route::post('/warranty',    [AssetTransitionController::class, 'toWarranty'])     ->name('warranty');
                Route::post('/transfer',    [AssetTransitionController::class, 'transfer'])       ->name('transfer');
                Route::post('/arrival',     [AssetTransitionController::class, 'arrivalConfirm']) ->name('arrival');
            });
            // Desincorporaciones — requieren permiso de solicitud de baja
            Route::middleware('permission:tech.assets.disposal.request|assets.disposal.request')->group(function () {
                Route::post('/baja',     [AssetTransitionController::class, 'toBaja'])     ->name('baja');
                Route::post('/donation', [AssetTransitionController::class, 'toDonation']) ->name('donation');
                Route::post('/sale',     [AssetTransitionController::class, 'toSale'])     ->name('sale');
            });
        });

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRACIÓN — USUARIOS
    |--------------------------------------------------------------------------
    */
    Route::middleware(['can:users.manage', 'throttle:40,1'])->group(function () {

        Route::get('/admin/users', [UserController::class, 'index'])
            ->name('users.index');

        Route::get('/admin/users/create', [UserController::class, 'create'])
            ->name('users.create');

        Route::post('/admin/users', [UserController::class, 'store'])
            ->name('users.store');

        Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])
            ->name('users.edit');

        Route::put('/admin/users/{user}', [UserController::class, 'update'])
            ->name('users.update');

        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy');

        Route::post('/admin/users/{user}/revoke-sessions', [UserController::class, 'revokeAllSessions'])
            ->name('users.revoke-sessions');

        Route::post('/admin/users/{user}/unlock', [UserController::class, 'unlock'])
            ->name('users.unlock');
    });

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRACIÓN — CONFIGURACIÓN DEL SISTEMA
    |--------------------------------------------------------------------------
    */
    // ── Módulo Respaldo y Recuperación (solo Admin) ─────────────────────────────
    Route::prefix('admin/backup')->name('admin.backup.')->middleware('role:Admin')->group(function () {
        Route::get('/',                              [\App\Http\Controllers\BackupController::class, 'index'])   ->name('index');
        Route::post('/run',                          [\App\Http\Controllers\BackupController::class, 'run'])     ->name('run');
        Route::get('/download/{filename}',           [\App\Http\Controllers\BackupController::class, 'download'])->name('download');
        Route::delete('/{backup}',                   [\App\Http\Controllers\BackupController::class, 'destroy']) ->name('destroy');
    });

    Route::middleware('can:admin.settings')->prefix('admin')->name('admin.')->group(function () {

        Route::get('/settings', [SettingsController::class, 'index'])
            ->name('settings');

        Route::put('/settings', [SettingsController::class, 'update'])
            ->name('settings.update');

        // Plantillas de asignación
        Route::get('/assignment-templates', [AssignmentTemplateController::class, 'index'])
            ->name('assignment-templates.index');

        Route::get('/assignment-templates/create', [AssignmentTemplateController::class, 'create'])
            ->name('assignment-templates.create');

        Route::post('/assignment-templates', [AssignmentTemplateController::class, 'store'])
            ->name('assignment-templates.store');

        Route::get('/assignment-templates/{assignmentTemplate}/edit', [AssignmentTemplateController::class, 'edit'])
            ->name('assignment-templates.edit');

        Route::put('/assignment-templates/{assignmentTemplate}', [AssignmentTemplateController::class, 'update'])
            ->name('assignment-templates.update');

        Route::patch('/assignment-templates/{assignmentTemplate}/toggle', [AssignmentTemplateController::class, 'toggleActive'])
            ->name('assignment-templates.toggle');

        // Plantillas Excel de Actas (configurable por instalación)
        Route::get('/acta-templates', [ActaExcelTemplateController::class, 'index'])
            ->name('acta-templates.index');
        Route::get('/acta-templates/ti', [ActaExcelTemplateController::class, 'index'])
            ->defaults('category', 'TI')
            ->name('acta-templates.ti');
        Route::get('/acta-templates/otro', [ActaExcelTemplateController::class, 'index'])
            ->defaults('category', 'OTRO')
            ->name('acta-templates.otro');
        Route::get('/acta-templates/mixta', [ActaExcelTemplateController::class, 'index'])
            ->defaults('category', 'ALL')
            ->name('acta-templates.mixta');
        Route::get('/acta-templates/category/{category}', [ActaExcelTemplateController::class, 'index'])
            ->name('acta-templates.category');
        Route::get('/acta-templates/create', [ActaExcelTemplateController::class, 'create'])
            ->name('acta-templates.create');
        Route::get('/acta-templates/create/ti', [ActaExcelTemplateController::class, 'create'])
            ->defaults('category', 'TI')
            ->name('acta-templates.create.ti');
        Route::get('/acta-templates/create/otro', [ActaExcelTemplateController::class, 'create'])
            ->defaults('category', 'OTRO')
            ->name('acta-templates.create.otro');
        Route::get('/acta-templates/create/mixta', [ActaExcelTemplateController::class, 'create'])
            ->defaults('category', 'ALL')
            ->name('acta-templates.create.mixta');
        Route::get('/acta-templates/create/{category}', [ActaExcelTemplateController::class, 'create'])
            ->name('acta-templates.create.category');
        Route::post('/acta-templates', [ActaExcelTemplateController::class, 'store'])
            ->name('acta-templates.store');
        Route::get('/acta-templates/{actaExcelTemplate}/edit', [ActaExcelTemplateController::class, 'edit'])
            ->name('acta-templates.edit');
        Route::put('/acta-templates/{actaExcelTemplate}', [ActaExcelTemplateController::class, 'update'])
            ->name('acta-templates.update');
        Route::patch('/acta-templates/{actaExcelTemplate}/toggle', [ActaExcelTemplateController::class, 'toggleActive'])
            ->name('acta-templates.toggle');

        // Mapeo de campos → celdas para una plantilla
        Route::get('/acta-templates/{actaExcelTemplate}/fields', [ActaExcelTemplateFieldController::class, 'index'])
            ->name('acta-templates.fields.index');
        Route::post('/acta-templates/{actaExcelTemplate}/fields', [ActaExcelTemplateFieldController::class, 'store'])
            ->name('acta-templates.fields.store');
        Route::put('/acta-templates/{actaExcelTemplate}/fields/{field}', [ActaExcelTemplateFieldController::class, 'update'])
            ->name('acta-templates.fields.update');
        Route::delete('/acta-templates/{actaExcelTemplate}/fields/{field}', [ActaExcelTemplateFieldController::class, 'destroy'])
            ->name('acta-templates.fields.destroy');
    });

    // API — plantilla por valor (para el modal de asignación)
    Route::get('/api/assignment-templates/for-value', [AssignmentTemplateController::class, 'forValue'])
        ->name('api.assignment-templates.for-value')
        ->middleware('auth');

    /*
    |--------------------------------------------------------------------------
    | TIPOS TI — alias tech.types.* (siempre categoría TI)
    |--------------------------------------------------------------------------
    */
    Route::prefix('tech/types')->name('tech.types.')->middleware('can:tech.types.view')->group(function () {

        Route::get('/', function () {
            return app(\App\Http\Controllers\AssetTypeController::class)->index('TI');
        })->name('index');

        Route::get('/create', function () {
            return app(\App\Http\Controllers\AssetTypeController::class)->create('TI');
        })->name('create')->middleware('can:tech.types.create');

        Route::get('/{assetType}/edit', [AssetTypeController::class, 'edit'])
            ->name('edit')->middleware('can:tech.types.edit');

        Route::put('/{assetType}', [AssetTypeController::class, 'update'])
            ->name('update')->middleware('can:tech.types.edit');
    });

    Route::middleware('can:branches.manage')->group(function () {

        Route::get('/branches', [BranchController::class, 'index'])
            ->name('branches.index');

        Route::get('/branches/create', [BranchController::class, 'create'])
            ->name('branches.create');

        Route::post('/branches', [BranchController::class, 'store'])
            ->name('branches.store');
    });

    /*
    |--------------------------------------------------------------------------
    | CATEGORÍAS (placeholder)
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:categories.manage')->group(function () {

        Route::get('/categories', [CategoriesController::class, 'index'])
            ->name('categories.index');

        Route::post('/categories', [CategoriesController::class, 'store'])
            ->name('categories.store');

        Route::put('/categories/{id}', [CategoriesController::class, 'update'])
            ->name('categories.update');

        Route::delete('/categories/{id}', [CategoriesController::class, 'destroy'])
            ->name('categories.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | ESTADOS (placeholder)
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:statuses.manage')->group(function () {

        Route::get('/statuses', [StatusController::class, 'index'])
            ->name('statuses.index');

        Route::post('/statuses', [StatusController::class, 'store'])
            ->name('statuses.store');

        Route::put('/statuses/{status}', [StatusController::class, 'update'])
            ->name('statuses.update');

        Route::delete('/statuses/{status}', [StatusController::class, 'destroy'])
            ->name('statuses.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | AUDITORÍA / REPORTES
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:reports.view')->group(function () {

        Route::get('/audit', [AuditController::class, 'index'])
            ->name('audit.index');

        Route::get('/reports/global', [ReportController::class, 'global'])
            ->name('reports.global')
            ->middleware('can:reports.global');

        Route::get('/reports/collaborators/export', [ReportController::class, 'collaboratorsExport'])
            ->name('reports.collaborators.export')
            ->middleware('can:reports.view');
    });
});
