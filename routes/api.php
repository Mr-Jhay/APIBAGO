<?php

use App\Http\Controllers\gradelevelController;
use App\Http\Controllers\joinclassController;
use App\Http\Controllers\manage_curiculumController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TeacherReportController;
use App\Http\Controllers\strandcuriculumController;
use App\Http\Controllers\tblclassController;
use App\Http\Controllers\tblpositionController;
use App\Http\Controllers\tblpostionController;
use App\Http\Controllers\tblsectionController;
use App\Http\Controllers\tblsemesterController;
use App\Http\Controllers\tblstrandController;
use App\Http\Controllers\tblsubjectController;
use App\Http\Controllers\tblyearController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\ExamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;
//use App\Http\Controllers\MailController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register',[UsersController::class,'register']);//register user wala pa dito yung position for creating account
Route::post('login', [UsersController::class, 'login']);//login gooods na ito
Route::post('registerTeacher', [UsersController::class, 'registerTeacher']);
Route::post('registerstudent', [UsersController::class, 'registerstudent']);

Route::post('sendTestEmail', [MailController::class, 'sendTestEmail']);

Route::post('sendInvitation', [MailController::class, 'sendInvitation']);
//EmailController
Route::group([
    "middleware" => "auth:sanctum"
], function() {
    Route::post('logout', [UsersController::class, 'logout']);
    //ADMIN
    Route::post('addyear', [tblyearController::class, 'addyear']);//pag add ng year
    Route::get('viewyear', [tblyearController::class, 'viewyear']);//view lahat ng na add na year
    Route::put('updateyear/{id}', [tblyearController::class, 'updateyear']);  // Update an existing year
    Route::delete('deleteyear/{id}', [tblyearController::class, 'deleteyear']);  // Delete a year

    Route::post('addsubject', [tblsubjectController::class, 'addsubject']);
    Route::get('viewsubject', [tblsubjectController::class, 'viewsubject']);
    Route::put('updatesubject/{id}', [tblsubjectController::class, 'updatesubject']);
    Route::delete('deletesubject/{id}', [tblsubjectController::class, 'deletesubject']);
  
    Route::post('addstrand', [tblstrandController::class, 'addstrand']);//add ng strand sa admin
    Route::get('viewstrand', [tblstrandController::class, 'viewstrand']);//view ng lahat ng strand na na add ni admin
    Route::put('strands/{id}', [tblstrandController::class, 'updateStrand']);
    Route::delete('strands/{id}', [tblstrandController::class, 'deleteStrand']);

   /// Route::post('addsemester', [tblsemesterController::class, 'addsemester']);//add ng semester sa admin
   // Route::get('viewsemester', [tblsemesterController::class, 'viewsemester']);//view ng lahat ng semester na na add ni admin
   // Route::put('updatesemester/{id}', [tblsemesterController::class, 'updatesemester']);
   // Route::delete('deletesemester/{id}', [tblsemesterController::class, 'deletesemester']);
    //Route::put('togglesemesterstatus/{id}', [tblsemesterController::class, 'toggleStatus']);//

    Route::post('addsection', [tblsectionController::class, 'addsection']);//add ng section sa admin
    Route::get('viewsection', [tblsectionController::class, 'viewsection']);//view ng lahat ng availble section na na add ni admin
    Route::put('updatesection/{id}', [tblsectionController::class, 'updatesection']);
    Route::delete('deletesection/{id}', [tblsectionController::class, 'deletesection']);

  //  Route::post('addgradelevel', [gradelevelController::class, 'addgradelevel']);//add view ng lahat ng grade level g11 or 12
    //Route::get('viewgradelevel', [gradelevelController::class, 'viewgradelevel']);//view ng lahat ng grade level g11 or 12 na na add ni admin
    //Route::put('updategradelevel/{id}', [gradelevelController::class, 'updategradeLevel']);
    //Route::delete('deletegradelevel/{id}', [gradelevelController::class, 'deletegradeLevel']);

    Route::post('addposition', [tblpositionController::class, 'addposition']);//add view ng lahat position
    Route::get('viewposition', [tblpositionController::class, 'viewposition']);//view ng lahat ng position
    Route::put('updateposition/{id}', [tblpositionController::class, 'updateposition']);
    Route::delete('deleteposition/{id}', [tblpositionController::class, 'deleteposition']);

    Route::post('addcuriculum', [manage_curiculumController::class, 'addcuriculum']); // Add curriculum
    Route::get('viewcuriculum3', [manage_curiculumController::class, 'viewCurriculum3']); 
    Route::get('viewcuriculum2', [manage_curiculumController::class, 'viewcuriculum2']); 
    Route::get('viewcuriculum', [manage_curiculumController::class, 'viewcuriculum']); // View all curriculum entries
    Route::put('updatecuriculum/{id}', [manage_curiculumController::class, 'updateCuriculum']); // Update curriculum
    Route::delete('deletecuriculum', [manage_curiculumController::class, 'deleteCuriculum']); // Delete curriculum
    Route::get('/counts', [UsersController::class, 'getCounts']); ///////dashboard statistics
   
    Route::get('userprofile', [UsersController::class, 'userprofile']);

    
    Route::get('viewAllTeachers', [UsersController::class, 'viewAllTeachers']);//admin view all teACHER
    Route::put('updateTeacher/{id}', [UsersController::class, 'updateTeacher']);

    Route::get('viewAllStudents2', [UsersController::class, 'viewAllStudents2']);//admin view all student
    Route::put('updateStudentDetails/{id}', [UsersController::class, 'updateStudentDetails']); //admin update all students account

    
    Route::put('/user/{user}/update-password', [UsersController::class, 'updateUserPassword']);//both teacher and student    Route::put('updateOwnPassword', [UsersController::class, 'updateOwnPassword']);
    
    Route::get('viewallusers', [UsersController::class, 'viewallusers']);

    
    Route::post('addcuri', [strandcuriculumController::class, 'addcuri']);// add curiculum
    Route::get('viewcuri', [strandcuriculumController::class, 'viewcuri']);//view curiculum
    Route::put('updatecuri/{id}', [strandcuriculumController::class,'updatecuri']);
    Route::delete('deletecuri/{id}', [strandcuriculumController::class,'deletecuri']);
    
    Route::post('updateStatus', [strandcuriculumController::class, 'updateStatus']);//change status

    Route::post('generatePDF', [ReportController::class, 'generatePDF']);
    Route::post('generateTeacherPDF', [TeacherReportController::class, 'generateTeacherPDF']);

    Route::get('getCounts', [UsersController::class, 'getCounts']);
    Route::get('getAllStudentsWithStrands', [UsersController::class, 'getAllStudentsWithStrands']);
   // getCounts
  // getAllStudentsWithStrands
    //TEACHER

    Route::post('addclass', [tblclassController::class, 'addclass']);//add class in teacher side
    Route::get('viewAllClassDetails', [tblclassController::class, 'viewAllClassDetails']);//ALL NG NI CREATE NI TEACHER NA CLASS
    Route::get('class/{class_id}', [tblclassController::class, 'showClass']);// PAG VIEW NG SPECIFIC CLASS
    Route::get('showClass2', [tblclassController::class, 'showClass2']);
    Route::get('curriculums', [tblclassController::class, 'getCurriculums']);

    Route::get('viewcuriculum/{id}', [tblclassController::class, 'getCurriculumDetails']);

    Route::get('viewcuriculum3', [manage_curiculumController::class, 'viewCurriculum3']); 
    Route::get('getCurriculumDetails/{id}', [tblclassController::class, 'getCurriculumDetails']);
    Route::get('getCurriculumDetails2', [tblclassController::class, 'getCurriculumDetails2']);

    Route::get('getAllCurriculums', [tblclassController::class, 'getAllCurriculums']);
    Route::get('getStrandsByCurriculum/{id}', [tblclassController::class, 'getStrandsByCurriculum']);
    Route::get('getStrandsByCurriculum/{id}', [tblclassController::class, 'getStrandsByCurriculum']);

    Route::get('getAllCurriculums9', [tblclassController::class, 'getAllCurriculums9']);
    Route::get('getAllStrandDetailsByCurriculum', [tblclassController::class, 'getAllStrandDetailsByCurriculum']);
    
    //viewExamForTeacher2

   
   // g'getCurriculumDetails{$id}'

    
   Route::post('addStudentToClass', [joinclassController::class, 'addStudentToClass']);//join ng teacher matic
   Route::post('approveStudentJoinRequest', [joinclassController::class, 'approveStudentJoinRequest']);// Teacher approves or rejects a join request
   Route::get('listStudentsInClass', [joinclassController::class, 'listStudentsInClass']);//list of Students In Class
   Route::get('listStudentsInClass2/{class_id}', [joinclassController::class, 'listStudentsInClass2']);// Teacher fetches approved students in a class
   Route::get('listStudentsInClass3/{class_id}', [joinclassController::class, 'listStudentsInClass3']);// Teacher fetches pending join requests for a class
  // Route::get('listStudentsInClass4/{class_id}', [joinclassController::class, 'listStudentsInClass4']);//list ng mga kaklase
   
   Route::post('addExam', [ExamController::class, 'addExam']);//pag add ng exam
   Route::post('addExam2', [ExamController::class, 'addExam2']); // Add exam with total points and items
   Route::get('view-exam/{exam_id}', [ExamController::class, 'viewExamForTeacher']); // View exam in teacher side
   Route::get('viewExamForTeacher2', [ExamController::class, 'viewExamForTeacher2']); // View exam with total points
   Route::post('publishExam/{exam_id}', [ExamController::class, 'publishExam']);
  

// Route to get the approved classes for a student
Route::get('StudentApprovedClasses', [joinclassController::class, 'getStudentApprovedClasses']);



// Route for teachers to approve student join requests
Route::post('approveStudentJoinRequest', [joinclassController::class, 'approveStudentJoinRequest']);

// Route to list all students in a specific class (approved)
Route::get('classes/{class_id}/students/approved', [joinclassController::class, 'listStudentsInClass2']);

// Route to list all students with pending join requests in a specific class
Route::get('classes/{class_id}/students/pending', [joinclassController::class, 'listStudentsInClass3']);

Route::get('viewAllStudents', [joinclassController::class, 'viewAllApprovedStudents']);

// Additional routes for other operations
Route::post('addStudentToClass', [joinclassController::class, 'addStudentToClass']);
Route::get('listStudentsInClass', [joinclassController::class, 'listStudentsInClass']);

//ito raquel bago 
Route::get('tblclass/{classtable_id}/exams', [YourController::class, 'viewAllExamsInClass']);//view all exam inside the classrom
Route::get('tblclass/{classtable_id}/exam/{exam_id}', [YourController::class, 'viewExamDetails']);//if the choose one of the exam you will see all the questions


//list of all created exam inside the classroom
Route::get('tblclass/{classtable_id}/exams', [ExamController::class, 'viewAllExamsInClass']);

//if you click the view exam in the exam list ito view the specific exam
Route::get('tblclass/{classtable_id}/exam/{exam_id}', [ExamController::class, 'viewExamDetails']);


   
   
    //STUDENTS
    

    Route::post('jcstudent', [joinclassController::class, 'jcstudent']);//join the student in class
    Route::post('jcstudent2', [joinclassController::class, 'jcstudent2']);// Student joins a class using gen_code

    Route::get('getStudentClassrooms', [tblclassController::class, 'getStudentClassrooms']);


    Route::get('getStudentClassroomDetails', [tblclassController::class, 'getStudentClassroomDetails']); //all the subject only
    Route::get('getStudentClassroomDetails2', [tblclassController::class, 'getStudentClassroomDetails2']);//daitails of each subject
   
    Route::get('viewExam2/{exam_id}', [ExamController::class, 'viewExam2']);//pag view ng student sa exam then meron ng suffle
    Route::post('submitExam8/{exam_id}', [ExamController::class, 'submitExam8']);
    Route::get('listStudentsInClass4/{class_id}', [joinclassController::class, 'listStudentsInClass4']);//list ng mga kaklase
    
   // submitExam8

 

});
