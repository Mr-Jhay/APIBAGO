<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\tblyearController;
use App\Http\Controllers\tblsubjectController;
use App\Http\Controllers\tblstrandController;
use App\Http\Controllers\tblsemesterController;
use App\Http\Controllers\tblsectionController;
use App\Http\Controllers\tblpostionController;
use App\Http\Controllers\gradelevelController;
use App\Http\Controllers\tblpositionController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register',[UsersController::class,'register']);//register user wala pa dito yung position for creating account
Route::post('login', [UsersController::class, 'login']);//login gooods na ito

Route::group([
    "middleware" => "auth:sanctum"
], function() {
    Route::post('logout', [UsersController::class, 'logout']);
    //ADMIN
    Route::post('addyear', [tblyearController::class, 'addyear']);//pag add ng year
    Route::get('viewyear', [tblyearController::class, 'viewyear']);//view lahat ng na add na year
    Route::post('addsubject', [tblsubjectController::class, 'addsubject']);//add ng subject sa admin
    Route::get('viewsubject', [tblsubjectController::class, 'viewsubject']);//view ng lahat ng subject na na add ni admin
    Route::post('addstrand', [tblstrandController::class, 'addstrand']);//add ng strand sa admin
    Route::get('viewstrand', [tblstrandController::class, 'viewstrand']);//view ng lahat ng strand na na add ni admin
    Route::post('addsemester', [tblsemesterController::class, 'addsemester']);//add ng semester sa admin
    Route::get('viewsemester', [tblsemesterController::class, 'viewsemester']);//view ng lahat ng semester na na add ni admin
    Route::post('addsection', [tblsectionController::class, 'addsection']);//add ng section sa admin
    Route::get('viewsection', [tblsectionController::class, 'viewsection']);//view ng lahat ng availble section na na add ni admin
    Route::post('addgradelevel', [gradelevelController::class, 'addgradelevel']);//add view ng lahat ng grade level g11 or 12
    Route::get('viewgradelevel', [gradelevelController::class, 'viewgradelevel']);//view ng lahat ng grade level g11 or 12 na na add ni admin
    Route::post('addposition', [tblpositionController::class, 'addposition']);//add view ng lahat position
    Route::get('viewposition', [tblpositionController::class, 'view']);//view ng lahat ng position

    Route::get('userprofile', [UsersController::class, 'userprofile']);
    Route::post('registerTeacher', [UsersController::class, 'registerTeacher']);
    Route::post('registerstudent', [UsersController::class, 'registerstudent']);
   
    //TEACHER

    //STUDENTS


});
