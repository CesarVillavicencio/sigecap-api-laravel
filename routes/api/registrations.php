<?php

use App\Http\Controllers\Api\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RegistrationController::class, 'index'])->name('index');
Route::get('/{id}', [RegistrationController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [RegistrationController::class, 'store'])->name('store');
Route::delete('/{id}', [RegistrationController::class, 'destroy'])->whereNumber('id')->name('destroy');
Route::patch('/{id}', [RegistrationController::class, 'update'])->whereNumber('id')->name('update');
Route::get('/{id}/qr.svg', [RegistrationController::class, 'qr'])->whereNumber('id')->name('qr');
