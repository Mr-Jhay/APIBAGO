<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\smsController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/send-sms', [smsController::class, 'sendSms']);
