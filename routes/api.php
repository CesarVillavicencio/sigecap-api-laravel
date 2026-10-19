<?php

use Illuminate\Support\Facades\Route;

Route::name('test.')->prefix('test')->group(__DIR__.'/api/test.php');

// Laravel agrega /api; las rutas de negocio quedan bajo /api/v1.
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('public')->name('public.')->group(function () {
        Route::group([], __DIR__.'/api/public-courses.php');
        Route::group([], __DIR__.'/api/public-course-editions.php');
    });
    Route::prefix('health')->name('health.')->group(__DIR__.'/api/health.php');
    Route::prefix('dependencies')->name('dependencies.')->group(__DIR__.'/api/dependencies.php');
    Route::prefix('courses')->name('courses.')->group(__DIR__.'/api/courses.php');
    Route::prefix('participants')->name('participants.')->group(__DIR__.'/api/participants.php');
    Route::prefix('course-editions')->name('course-editions.')->group(__DIR__.'/api/course-editions.php');
    Route::prefix('registrations')->name('registrations.')->group(__DIR__.'/api/registrations.php');
    Route::prefix('instructors')->name('instructors.')->group(__DIR__.'/api/instructors.php');
    Route::prefix('course-edition-instructors')->name('course-edition-instructors.')->group(__DIR__.'/api/course-edition-instructors.php');
    Route::prefix('course-edition-modules')->name('course-edition-modules.')->group(__DIR__.'/api/course-edition-modules.php');
    Route::prefix('attendances')->name('attendances.')->group(__DIR__.'/api/attendances.php');
    Route::prefix('certificates')->name('certificates.')->group(__DIR__.'/api/certificates.php');
});
