<?php

use App\Http\Controllers\Api\CourseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CourseController::class, 'index'])->name('index');
Route::get('/{id}', [CourseController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [CourseController::class, 'store'])->name('store');
Route::patch('/{id}', [CourseController::class, 'update'])->whereNumber('id')->name('update');
Route::delete('/{id}', [CourseController::class, 'destroy'])->whereNumber('id')->name('destroy');
Route::get('/{id}/qr.svg', [CourseController::class, 'qr'])->whereNumber('id')->name('qr');
