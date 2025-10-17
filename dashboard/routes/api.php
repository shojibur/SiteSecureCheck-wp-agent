<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::post('/agent/webhook', [WebhookController::class,'handle'])->name('agent.webhook');

