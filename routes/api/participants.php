<?php

use App\Http\Controllers\Api\ParticipantController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ParticipantController::class, 'index'])->name('index');
Route::get('/{id}', [ParticipantController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [ParticipantController::class, 'store'])->name('store');
Route::delete('/{id}', [ParticipantController::class, 'destroy'])->whereNumber('id')->name('destroy');
Route::patch('/{id}', [ParticipantController::class, 'update'])->whereNumber('id')->name('update');
