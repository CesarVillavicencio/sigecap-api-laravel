<?php

use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

// test
Route::get('test',             [TestController::class, 'test'])->name('getPartidas');

Route::get('pdfTest',          [TestController::class, 'pdf'])->name('getPDF');
