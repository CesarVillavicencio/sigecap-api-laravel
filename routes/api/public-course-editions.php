<?php

use App\Http\Controllers\Api\PublicCourseEditionController;
use Illuminate\Support\Facades\Route;

Route::get('/c/{public_token}', [PublicCourseEditionController::class, 'show'])->where('public_token', '[a-fA-F0-9]{64}')->name('course-editions.show');
Route::post('/c/{public_token}/inscripciones', [PublicCourseEditionController::class, 'register'])->where('public_token', '[a-fA-F0-9]{64}')->name('course-editions.register');
