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
use App\Http\Controllers\smsController;
//use App\Http\Controllers\MailController;
use App\Http\Controllers\FeedbackController;


Route::post('/check-id', [UsersController::class, 'checkId']); /// check if idnumber is = to admin
Route::get('/user', function (Request $request) {
  
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register',[UsersController::class,'register']);//register user wala pa dito yung position for creating account
Route::post('login', [UsersController::class, 'login']);//login gooods na ito
Route::post('registerTeacher', [UsersController::class, 'registerTeacher']);
Route::post('bulkRegisterstudent', [UsersController::class, 'bulkRegisterstudent']);//pag regiter gamit excel
Route::post('registerstudent', [UsersController::class, 'registerstudent']);

Route::post('sendTestEmail', [MailController::class, 'sendTestEmail']);

Route::post('sendInvitation', [MailController::class, 'sendInvitation']);

Route::post('store99', [AnnouncementController::class, 'store99']);

Route::post('sendSms2', [smsController::class, 'sendSms2']);


Route::post('/send-welcome-mail', [MailController::class, 'sendWelcomeMail']);

Route::post('/inviteStudentByEmail', [MailController::class, 'inviteStudentByEmail']);
Route::post('sendVerificationCode', [UsersController::class, 'sendVerificationCode']);
Route::post('updatePassword', [UsersController::class, 'updatePassword']);


Route::post('/import_excel_post4', [UsersController::class, 'import_excel_post4']); //upload of student data using excell register
Route::post('/import_excel_post2', [UsersController::class, 'import_excel_post2']); //upload of teacher data using excell register


Route::get('/export_result', [ExamController::class, 'export_result']); //download results



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
    Route::patch('toggleyear/{id}', [tblyearController::class, 'toggleActive']);


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
    Route::get('viewcuriculumforaddclass', [manage_curiculumController::class, 'viewcuriculumforaddclass']); //pag view ng available subject
 
    Route::get('viewcuriculum', [manage_curiculumController::class, 'viewcuriculum']); // View all curriculum entries


    //updated for edit curiculum
    Route::put('updateCurriculum/{id}', [manage_curiculumController::class, 'updateCurriculum']); // Update curriculum 
    Route::delete('deletecuriculum', [manage_curiculumController::class, 'deleteCuriculum']); // Delete curriculum
    Route::get('/counts', [UsersController::class, 'getCounts']); ///////dashboard statistics
   
    Route::get('userprofile', [UsersController::class, 'userprofile']);

    
    Route::get('viewAllTeachers', [UsersController::class, 'viewAllTeachers']);//admin view all teACHER
    Route::put('updateTeacher/{id}', [UsersController::class, 'updateTeacher']);

    Route::get('viewAllStudents2', [UsersController::class, 'viewAllStudents2']);//admin view all student
    Route::put('updateStudentDetails/{id}', [UsersController::class, 'updateStudentDetails']); //admin update all students account

    
    Route::put('/user/{user}/update-password', [UsersController::class, 'updateUserPassword']);//both teacher and student
    
    Route::put('updateOwnPassword', [UsersController::class, 'updateOwnPassword']);
    
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
    Route::get('/downloadExamInstructionsPDF/{examId}', [ExamController::class, 'downloadExamInstructionsPDF']);
    //yung may time ganun
    Route::post('createExam', [ExamController::class, 'createExam']);

    //yung mga question
    Route::post('addQuestionsToExam/{id}', [ExamController::class, 'addQuestionsToExam']);

    //ito yung sa teacherside na fetch niya yung schedule tapos saka palang mag add pag ni click
   Route::get('getAllExamsByClass/{id}', [ExamController::class, 'getAllExamsByClass']);

   //specific schedule
   Route::get('getExam/{id}', [ExamController::class, 'getExam']);

   //updateExam
   Route::put('updateExam/{id}', [ExamController::class, 'updateExam']);
//pag view nung nagawana bagong questions
   Route::get('getExamInstructionAndCorrectAnswers/{id}', [ExamController::class, 'getExamInstructionAndCorrectAnswers']);

   

   Route::delete('deleteCurriculum/{id}', [manage_curiculumController::class, 'deleteCurriculum']); 

  //pag store sa testbank
   Route::post('storetestbank', [ExamController::class, 'storetestbank']);

   Route::get('viewTestBank', [ExamController::class, 'viewTestBank']);

    

   Route::put('updateClassStatus/{id}', [tblclassController::class, 'updateClassStatus']);//archive
   Route::put('updateaddclass/{id}', [tblclassController::class, 'updateaddclass']);//pag update ng class
    Route::post('addclass', [tblclassController::class, 'addclass']);//add class in teacher side
    Route::get('viewAllClassDetails', [tblclassController::class, 'viewAllClassDetails']);//ALL NG NI CREATE NI TEACHER NA CLASS
    Route::get('class/{id}', [tblclassController::class, 'showClass']);// PAG VIEW NG SPECIFIC CLASS

    Route::get('showClass2', [tblclassController::class, 'showClass2']);
    Route::get('curriculums', [tblclassController::class, 'getCurriculums']);

    Route::get('viewcuriculum/{id}', [tblclassController::class, 'getCurriculumDetails']);

    Route::get('viewcuriculum3', [manage_curiculumController::class, 'viewCurriculum3']); 
    Route::get('getCurriculumDetails/{id}', [tblclassController::class, 'getCurriculumDetails']);

   // Route::patch('/exam/{id}/archive', [ExamController::class, 'archiveExam']);


    //enter the class_id
    //Route::get('student/classroom-details/{class_id}', [tblclassController::class, 'getCurriculumDetails2']);


    //getStudentClassroomDetails2

    Route::get('getAllCurriculums', [tblclassController::class, 'getAllCurriculums']);
    Route::get('getStrandsByCurriculum/{id}', [tblclassController::class, 'getStrandsByCurriculum']);
    Route::get('getStrandsByCurriculum/{id}', [tblclassController::class, 'getStrandsByCurriculum']);

    Route::get('getAllCurriculums9', [tblclassController::class, 'getAllCurriculums9']);
    Route::get('getAllStrandDetailsByCurriculum', [tblclassController::class, 'getAllStrandDetailsByCurriculum']);
    
    //viewExamForTeacher2

   
   // g'getCurriculumDetails{$id}'

   Route::delete('kickStudentFromClass/{id}/{id2}', [joinclassController::class, 'kickStudentFromClass']);//kick ng student //id ng class //id ng student
   Route::post('addwocode', [joinclassController::class, 'addwocode']);//join ng teacher matic
   Route::post('approveStudentJoinRequest', [joinclassController::class, 'approveStudentJoinRequest']);// Teacher approves or rejects a join request
   Route::get('listStudentsInClass', [joinclassController::class, 'listStudentsInClass']);//list of Students In Class
   Route::get('listStudentsInClassGendertotal/{id}', [joinclassController::class, 'listStudentsInClassGendertotal']);//list of total with gender

    // Method to list all students with approved join requests in a class WITH total_students , MALE FEMALE
   Route::get('listStudentsInClass2/{id}', [joinclassController::class, 'listStudentsInClass2']);// Teacher fetches approved students in a class 
   Route::get('listStudentsInClass3/{id}', [joinclassController::class, 'listStudentsInClass3']);// Teacher fetches pending join requests for a class
  // Route::get('listStudentsInClass4/{class_id}', [joinclassController::class, 'listStudentsInClass4']);//list ng mga kaklase
   

 // getStudentClassroomDetails2
   Route::post('addExam', [ExamController::class, 'addExam']);//pag add ng exam


   

Route::get('/tblclass/{id}/exams', [ExamController::class, 'viewAllExamsInClass']);

// Fetch all exams for a specific class
Route::get('/tblclass/{id}/exams2', [ExamController::class, 'viewAllExamsInClass2']);
Route::get('viewAllExamsInAllClasses', [ExamController::class, 'viewAllExamsInAllClasses']);

// Create a new exam
Route::post('/addExam2', [ExamController::class, 'addExam2']);

// View details of a specific exam (for students)
Route::get('/tblclass/{class_id}/exam/{exam_id}', [ExamController::class, 'viewExam']);

// View details of a specific exam (for teachers)
//Route::get('/exams/{exam_id}/details', 'ExamController@viewExamDetails');

//view the details of exam 
Route::get('/exams/{id}/details', [ExamController::class, 'viewExamDetails']);
////view the details of exam  with points and total item
Route::get('/exams/{id}/details2', [ExamController::class, 'viewExamDetails2']);


Route::get('/exam/{id}', [ExamController::class, 'viewExamForTeacher']);

// Start an exam (for students)
Route::post('/startExam/{id}', [ExamController::class, 'startExam']);

// Submit answers for an exam (for students)
Route::post('/submitExam/{exam_id}', [ExamController::class, 'submitExam']);

// Get exam results (for students)
Route::get('/exam/{exam_id}/results', [ExamController::class, 'getResults']);

// Update an existing exam
Route::put('/exam/{exam_id}', [ExamController::class, 'updateExam']);

// Delete an exam
Route::delete('/exam/{exam_id}', [ExamController::class, 'deleteExam']);

//publish exam to students
Route::post('/exams/publish2/{id}', [ExamController::class, 'publish2']);

Route::post('/exam/{examId}/publish', [ExamController::class, 'publish']);

  


   // Fetch all exams for a specific class
   Route::get('/tblclass/{class_id}/exams', [ExamController::class, 'viewAllExamsInClass']);

   // Create a new exam
   Route::post('/addExam2', [ExamController::class, 'addExam2']);

   // View details of a specific exam (for students)
   Route::get('/tblclass/{class_id}/exam/{exam_id}', [ExamController::class, 'viewExam']);

   // View details of a specific exam (for teachers)
   Route::get('/exam/{exam_id}', [ExamController::class, 'viewExamForTeacher']);

   // Start an exam (for students)
   Route::post('/startExam/{examId}', [ExamController::class, 'startExam']);

   // Submit answers for an exam (for students)
   Route::post('/submitExam/{exam_id}', [ExamController::class, 'submitExam']);

   // Get exam results (for students)
   Route::get('/exam/{exam_id}/results', [ExamController::class, 'getResults']);

   // Update an existing exam
   Route::put('/exam/{exam_id}', [ExamController::class, 'updateExam']);

   // Delete an exam
   Route::delete('/exam/{exam_id}', [ExamController::class, 'deleteExam']);

   // Archive an exam
Route::delete('/exam/{exam_id}', [ExamController::class, 'archiveExam']);


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
Route::get('tblclass/{classtable_id}/exams', [ExamController::class, 'viewAllExamsInClass']);//view all exam inside the classrom
Route::get('tblclass/{classtable_id}/exam/{exam_id}', [ExamController::class, 'viewExamDetails']);//if the choose one of the exam you will see all the questions


//HOW HOW MAN CLASS THAN TEACHE HANDLE
 Route::get('showClasstotal', [tblclassController::class, 'showClasstotal']);


 Route::get('getAllStudentResults', [ExamController::class, 'getAllStudentResults']);

 Route::get('getComments', [FeedbackController::class, 'getComments']); //getComments
 Route::get('getComments2', [FeedbackController::class, 'getComments2']); //get comment in specific exam


 //item analysis
 Route::get('itemAnalysis', [ExamController::class, 'itemAnalysis']);
 Route::get('itemAnalysis2', [ExamController::class, 'itemAnalysis2']);

 Route::get('analysisStudent/{id}', [ExamController::class, 'analysisStudent']);
 //analysisStudent

 Route::put('updateQuestionsInExam/{id}', [ExamController::class, 'updateQuestionsInExam']);//pag update ng exam
 Route::post('createAndPublishExam', [ExamController::class, 'createAndPublishExam']);//update sa bago whout clicking the publish
 Route::delete('deleteMultipleQuestions/{id}', [ExamController::class, 'deleteMultipleQuestions']);

 Route::get('viewquestion/{id}', [ExamController::class, 'viewquestion']);//pag view ng specific question need lang is yung id nung question
 Route::put('updateQuestion/{id}', [ExamController::class, 'updateQuestion']);//pag edit ng question need lang is yung id nung question
 Route::put('updateChoice/{id}/{id2}', [ExamController::class, 'updateChoice']);////pag edit ng choices need lang is yung id nung question

   
    //STUDENTS

    //pag view ng exam results sa class
    Route::get('getResultsallexam', [ExamController::class, 'getResultsallexam']);

    //finshi and unfinish exam
    Route::get('getResultsallexam2', [ExamController::class, 'getResultsallexam2']);
   
    Route::post('jcstudent', [joinclassController::class, 'jcstudent']);//join the student in class
    Route::post('jcstudent2', [joinclassController::class, 'jcstudent2']);// Student joins a class using gen_code

    Route::get('getStudentClassrooms', [tblclassController::class, 'getStudentClassrooms']);


   
   // Route::get('class/{class_id}', [tblclassController::class, 'getStudentClassroomDetails']); // Ensure the route is protected
   Route::get('class/{class_id}', [tblclassController::class, 'getStudentClassroomDetails']); 

    Route::get('getStudentClassroomDetails', [tblclassController::class, 'getStudentClassroomDetails']); //all the subject only
    Route::get('student/classroom-details/{id}', [tblclassController::class, 'getStudentClassroomDetails2']);//daitails of each subject
    Route::get('/classroom/{id}', [tblclassController::class, 'getSingleClassroomDetails']);

    
 
    Route::get('/class/{id}/published-exams', [ExamController::class, 'getPublishedExams']);
 
   //pag view ng mga exams sa student
    Route::get('exams/{id}', [ExamController::class, 'viewAllExams2']);
   
    Route::get('viewExam2/{id}', [ExamController::class, 'viewExam2']);//pag view ng student sa exam then meron ng suffle
   // Route::post('/exam/{id}/submitExam2', [ExamController::class, 'submitExam2']);//pag submit ng exam
    Route::get('getResults/{id}', [ExamController::class, 'getResults']);//pag view ng sagot
   // getResults
    //submitExam2
    Route::get('listStudentsInClass4/{class_id}', [joinclassController::class, 'listStudentsInClass4']);//list ng mga kaklase
    
 
    Route::get('getResults2', [ExamController::class, 'getResults2']);

    //new in testbank
    Route::get('viewExam2updated/{id}', [ExamController::class, 'viewExam2updated']);//pag view ng student sa exam then meron ng suffle testbank
    Route::post('/exam/{id}/submitExam2', [ExamController::class, 'submitExam2']);//pag submit ng exam
    Route::get('getResultswithtestbank/{id}', [ExamController::class, 'getResultswithtestbank']);//pag view the result

    Route::post('commentfeedback/{id}', [FeedbackController::class, 'commentfeedback']);//pag submit ng exam


    Route::get('viewExam2updated2/{id}', [ExamController::class, 'viewExam2updated2']);//pag view ng student sa exam then meron ng suffle testbank2
 
});