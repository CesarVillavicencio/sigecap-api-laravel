<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/ready', [HealthController::class, 'ready'])->name('ready');
