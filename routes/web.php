<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\smsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ExamController;


Route::get('/', function () {
    return view('welcome');
});

// Route::get('/import-excel', [UsersController::class, 'import_excel']);
// Route::post('/import-excel', [UsersController::class, 'import_excel_post']);


Route::get('/import-excel', [UsersController::class, 'import_excel'])->name('import_excel');
Route::post('/import-excel4', [UsersController::class, 'import_excel_post4'])->name('import_excel_post4');


//Route::post('/import-excel2', [UsersController::class, 'import_excel_post2']);
Route::get('/send-sms', [smsController::class, 'sendSms']);
Route::get('/export-excel', [UsersController::class, 'export_excel']);
Route::get('/export_result', [ExamController::class, 'export_result']);

// Route::get('/get-all-student-results', [YourControllerName::class, 'getAllStudentResults'])
//     ->middleware('auth') // Optional: ensures the user is authenticated
//     ->name('getAllStudentResults');