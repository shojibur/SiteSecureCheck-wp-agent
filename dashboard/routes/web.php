<?php

use App\Http\Controllers\SiteController;
use App\Http\Controllers\TestPluginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function(){
    return Auth::check() ? redirect('/sites') : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn()=>redirect('/sites'))->name('dashboard');
    Route::resource('sites', SiteController::class);
    Route::post('/sites/{site}/scan', [SiteController::class,'scan'])->name('sites.scan');
    Route::post('/sites/{site}/check-connection', [SiteController::class,'checkConnection'])->name('sites.check-connection');
    Route::post('/sites/{site}/scans/{scan}/apply', [SiteController::class,'applyFixes'])->name('sites.scans.apply');
    Route::delete('/sites/{site}/scans/{scan}', [SiteController::class,'deleteScan'])->name('sites.scans.delete');

    // Test/Debug routes (until Agent is implemented)
    Route::prefix('sites/{site}/test')->group(function () {
        Route::get('/connection', [TestPluginController::class, 'testConnection'])->name('sites.test.connection');
        Route::get('/auth', [TestPluginController::class, 'testAuth'])->name('sites.test.auth');
        Route::get('/security-state', [TestPluginController::class, 'getSecurityState'])->name('sites.test.security-state');
        Route::post('/apply-sample', [TestPluginController::class, 'applySampleFixes'])->name('sites.test.apply-sample');
        Route::post('/mock-scan', [TestPluginController::class, 'createMockScan'])->name('sites.test.mock-scan');
    });
});

require __DIR__.'/auth.php';
