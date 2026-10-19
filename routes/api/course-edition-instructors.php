<?php

use App\Http\Controllers\Api\CourseEditionInstructorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CourseEditionInstructorController::class, 'index'])->name('index');
Route::get('/{id}', [CourseEditionInstructorController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [CourseEditionInstructorController::class, 'store'])->name('store');
Route::patch('/{id}', [CourseEditionInstructorController::class, 'update'])->whereNumber('id')->name('update');
Route::delete('/{id}', [CourseEditionInstructorController::class, 'destroy'])->whereNumber('id')->name('destroy');
