<?php

use App\Http\Controllers\Api\PublicCertificateController;
use Illuminate\Support\Facades\Route;

Route::get('/constancias/verificar/{token}', [PublicCertificateController::class, 'verify'])->where('token', '[a-fA-F0-9]{64}')->name('certificates.verify');
