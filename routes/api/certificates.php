<?php

use App\Http\Controllers\Api\CertificateController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CertificateController::class, 'index'])->name('index');
Route::get('/{id}/pdf', [CertificateController::class, 'pdf'])->whereNumber('id')->name('pdf');
Route::get('/{id}', [CertificateController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [CertificateController::class, 'store'])->name('store');
Route::patch('/{id}', [CertificateController::class, 'update'])->whereNumber('id')->name('update');
Route::delete('/{id}', [CertificateController::class, 'destroy'])->whereNumber('id')->name('destroy');
