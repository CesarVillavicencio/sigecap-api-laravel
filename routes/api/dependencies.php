<?php

use App\Http\Controllers\Api\DependencyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DependencyController::class, 'index'])->name('index');
Route::get('/{id}', [DependencyController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [DependencyController::class, 'store'])->name('store');
Route::delete('/{id}', [DependencyController::class, 'destroy'])->whereNumber('id')->name('destroy');
Route::patch('/{id}', [DependencyController::class, 'update'])->whereNumber('id')->name('update');
