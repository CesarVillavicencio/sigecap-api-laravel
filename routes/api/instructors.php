<?php

use App\Http\Controllers\Api\InstructorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InstructorController::class, 'index'])->name('index');
Route::get('/{id}', [InstructorController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [InstructorController::class, 'store'])->name('store');
Route::patch('/{id}', [InstructorController::class, 'update'])->whereNumber('id')->name('update');
Route::delete('/{id}', [InstructorController::class, 'destroy'])->whereNumber('id')->name('destroy');
