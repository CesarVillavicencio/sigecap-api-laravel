<?php

use App\Http\Controllers\Api\CourseEditionModuleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CourseEditionModuleController::class, 'index'])->name('index');
Route::get('/{id}', [CourseEditionModuleController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [CourseEditionModuleController::class, 'store'])->name('store');
Route::patch('/{id}', [CourseEditionModuleController::class, 'update'])->whereNumber('id')->name('update');
Route::delete('/{id}', [CourseEditionModuleController::class, 'destroy'])->whereNumber('id')->name('destroy');
