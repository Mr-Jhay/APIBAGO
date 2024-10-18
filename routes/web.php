<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\smsController;
use App\Http\Controllers\UsersController;


Route::get('/', function () {
    return view('welcome');
});

// Route::get('/import-excel', [UsersController::class, 'import_excel']);
// Route::post('/import-excel', [UsersController::class, 'import_excel_post']);


Route::get('/import-excel', [UsersController::class, 'import_excel'])->name('import_excel');
Route::post('/import-excel4', [UsersController::class, 'import_excel_post4'])->name('import_excel_post4');


//Route::post('/import-excel2', [UsersController::class, 'import_excel_post2']);
Route::get('/send-sms', [smsController::class, 'sendSms']);
