<?php

use App\Http\Controllers\InstallController;
use App\Http\Controllers\DebugController;
use Illuminate\Support\Facades\Route;


Route::get('/debug', [DebugController::class, 'test']);
Route::get('/', function () {
    // Check if installed, if not redirect to installer
    if (!file_exists(storage_path('installed'))) {
        return redirect('/install');
    }
    return view('welcome');
});

// Installation Routes - only available if not installed
Route::prefix('install')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('install.index');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('install.requirements');
    Route::get('/database', [InstallController::class, 'database'])->name('install.database');
    Route::post('/database/test', [InstallController::class, 'testDatabase'])->name('install.database.test');
    Route::post('/database/list', [InstallController::class, 'listDatabases'])->name('install.database.list');
    Route::post('/database/create', [InstallController::class, 'createDatabase'])->name('install.database.create');
    Route::post('/database/save', [InstallController::class, 'saveDatabaseConfig'])->name('install.database.save');
    Route::get('/administrator', [InstallController::class, 'administrator'])->name('install.administrator');
    Route::post('/administrator/validate', [InstallController::class, 'validateAdmin'])->name('install.administrator.validate');
    Route::get('/run', [InstallController::class, 'showInstall'])->name('install.run.show');
    Route::post('/run', [InstallController::class, 'install'])->name('install.run');
    Route::get('/complete', [InstallController::class, 'complete'])->name('install.complete');
});


