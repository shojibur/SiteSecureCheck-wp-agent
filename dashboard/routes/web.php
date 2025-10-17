<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function(){
    return Auth::check() ? redirect('/sites') : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn()=>redirect('/sites'))->name('dashboard');
    Route::resource('sites', SiteController::class);
    Route::post('/sites/{site}/scan', [SiteController::class,'scan'])->name('sites.scan');
    Route::post('/sites/{site}/scans/{scan}/apply', [SiteController::class,'applyFixes'])->name('sites.scans.apply');
});

require __DIR__.'/auth.php';
