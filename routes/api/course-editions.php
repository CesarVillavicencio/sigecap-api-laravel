<?php

use App\Http\Controllers\Api\CourseEditionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CourseEditionController::class, 'index'])->name('index');
Route::get('/{id}', [CourseEditionController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [CourseEditionController::class, 'store'])->name('store');
Route::delete('/{id}', [CourseEditionController::class, 'destroy'])->whereNumber('id')->name('destroy');
Route::patch('/{id}', [CourseEditionController::class, 'update'])->whereNumber('id')->name('update');
Route::get('/{id}/qr.svg', [CourseEditionController::class, 'qr'])->whereNumber('id')->name('qr');
