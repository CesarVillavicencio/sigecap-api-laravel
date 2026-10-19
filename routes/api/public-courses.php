<?php

use App\Http\Controllers\Api\PublicCourseController;
use Illuminate\Support\Facades\Route;

Route::get('/cursos', [PublicCourseController::class, 'index'])->name('courses.index');
Route::get('/cursos/{id}', [PublicCourseController::class, 'show'])->whereNumber('id')->name('courses.show');
Route::post('/cursos/{id}/inscripciones', [PublicCourseController::class, 'register'])->whereNumber('id')->name('courses.register');
