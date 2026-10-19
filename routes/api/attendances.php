<?php

use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AttendanceController::class, 'index'])->name('index');
Route::get('/{id}', [AttendanceController::class, 'show'])->whereNumber('id')->name('show');
Route::post('/', [AttendanceController::class, 'store'])->name('store');
Route::patch('/{id}', [AttendanceController::class, 'update'])->whereNumber('id')->name('update');
Route::delete('/{id}', [AttendanceController::class, 'destroy'])->whereNumber('id')->name('destroy');
