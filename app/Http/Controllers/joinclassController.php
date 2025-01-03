<?php

namespace App\Http\Controllers;

use App\Models\joinclass;
use App\Models\tblclass;
use App\Models\User;
use App\Models\tblstudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationMail;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Mail\TestMail;
use App\Mail\WelcomeMail;


class joinclassController extends Controller
{
    // Method for students to join a class using class_id and gen_code
    public function jcstudent2(Request $request)
{
    $request->validate([
        'status' => 'nullable|integer',
        'gen_code' => 'required|string'
    ]);

    $user = auth()->user();

    // Check if the user is a student
    if ($user->usertype !== 'student') {
        return response()->json([
            'error' => 'Unauthorized: Only students can join classes.'
        ], 403);
    }

    // Retrieve the student's strand_id
    $student = DB::table('tblstudent')->where('user_id', $user->id)->first();

    if (!$student) {
        return response()->json([
            'error' => 'Student record not found.'
        ], 404);
    }

    // Find the class by gen_code
    $class = DB::table('tblclass')->where('gen_code', $request->input('gen_code'))->first();

    if (!$class) {
        return response()->json([
            'error' => 'Class not found or code does not match.'
        ], 400);
    }

    // Check if the student's strand_id matches the class's strand_id
    if ($class->strand_id !== $student->strand_id) {
        return response()->json([
            'error' => 'You cannot join this class. Your strand does not match the class strand.'
        ], 403);
    }

    if ($class->section_id !== $student->section_id) {
        return response()->json([
            'error' => 'You cannot join this class. Your section does not match the class section.'
        ], 403);
    }
    // Create the joinClass record
    $joinClass = joinclass::create([
        'user_id' => $user->id,
        'class_id' => $class->id, // Use the class ID from the found class
        'status' => $request->input('status', 0) // default status is 0 if not provided
    ]);

    return response()->json($joinClass, 201);
}


// Method for adding students to a class without gen_code
public function addwocode(Request $request)
{
    // Validate incoming request
    $request->validate([
        'class_id' => 'required|exists:tblclass,id', // Class ID must be provided and exist in tblclass
        'user_ids' => 'required|array',
        'user_ids.*' => 'exists:users,id', // Ensure each ID exists in the users table
    ]);

    // Get the class ID from the request
    $classId = $request->input('class_id');
    
    // Get the currently authenticated teacher
    $teacher = auth()->user();
    
    // Retrieve the class by class_id
    $class = DB::table('tblclass')->where('id', $classId)->first();
    
    // Check if the teacher is associated with the class
    if ($class->user_id != $teacher->id) {
        return response()->json(['error' => 'Unauthorized: You are not the teacher of this class'], 403);
    }

    // Initialize arrays to store the joinclass records and already joined users
    $joinClasses = [];
    $alreadyJoinedUsers = [];

    // Loop through each user_id to create a joinclass record
    foreach ($request->input('user_ids') as $userId) {
        // Check if the user is already in the class
        $existingJoinClass = joinclass::where('user_id', $userId)->where('class_id', $class->id)->first();
        
        if ($existingJoinClass) {
            // If user already joined, add to the alreadyJoinedUsers array
            $alreadyJoinedUsers[] = $userId;
            continue; // Skip to the next user_id
        }

        // Create joinclass record
        $joinClasses[] = joinclass::create([
            'user_id' => $userId,
            'class_id' => $class->id,
            'status' => 1, // Set status to 1
        ]);
    }

    // If there are already joined users, return an error response
    if (!empty($alreadyJoinedUsers)) {
        return response()->json([
            'message' => 'Some users were not added because they have already joined the class.',
            'already_joined_users' => array_map(fn($userId) => User::find($userId)->lname, $alreadyJoinedUsers),
        ], 409); // 409 Conflict
    }

    // Return the newly created joinclass records
    return response()->json([
        'message' => 'Users added successfully.',
        'join_classes' => $joinClasses,
        'added_user_lastname' => array_map(fn($userId) => User::find($userId)->lname, $request->input('user_ids')),
    ], 201);
}


    

    // Method for students to join a class using gen_code only
    public function jcstudent3(Request $request)
    {
        $request->validate([
            'gen_code' => 'required|string'
        ]);

        $user = auth()->user();

        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can join classes.'
            ], 403);
        }

        $class = DB::table('tblclass')->where('gen_code', $request->input('gen_code'))->first();

        if (!$class) {
            return response()->json([
                'error' => 'Invalid class or gen_code does not match.'
            ], 400);
        }

        $joinClass = joinclass::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'status' => 0
        ]);

        return response()->json([
            'message' => 'Request to join the class submitted. Please wait for teacher approval.'
        ], 201);
    }

    // Method for teachers to approve students joining their class
    public function addStudentToClass(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:tblclass,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $user = auth()->user();

        if ($user->usertype !== 'teacher') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers can approve or reject class join requests.'
            ], 403);
        }

        $class = tblclass::find($request->input('class_id'));

        if (!$class || $class->user_id !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized: You do not have permission to modify this class.'
            ], 403);
        }

        foreach ($request->input('user_ids') as $userId) {
            $student = DB::table('users')->where('id', $userId)->first();

            if (!$student || $student->usertype !== 'student') {
                return response()->json([
                    'error' => 'Invalid user: One or more provided user IDs do not belong to a student.'
                ], 400);
            }

            $joinClass = joinclass::firstOrNew([
                'class_id' => $request->input('class_id'),
                'user_id' => $userId
            ]);

            if ($joinClass->exists) {
                continue;
            }

            $joinClass->status = 1;
            $joinClass->save();
        }

        return response()->json([
            'message' => 'Student requests status updated successfully.'
        ], 200);
    }

    // Method to list all students in a class
    public function listStudentsInClass(Request $request, $class_id)
    {
        $request->validate([
            'class_id' => 'required|exists:tblclass,id'
        ]);

        $user = $request->user();

        if ($user->usertype !== 'teacher' && $user->usertype !== 'admin') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers and admins can view students in a class.'
            ], 403);
        }

        $students = DB::table('users')
                    ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                    ->where('joinclass.class_id', $class_id)
                    ->where('users.usertype', 'student')
                    ->select('users.id','users.idnumber', 'users.fname', 'user.sex')
                    ->get();

        return response()->json($students, 200);
    }

    public function kickStudentFromClass(Request $request, $class_id, $student_id)
    {
        // Validate incoming request
        // $request->validate([
        //     'class_id' => 'required|exists:tblclass,id',
        //     'student_id' => 'required|exists:users,id',
        // ]);
    
        $user = $request->user();
    
        // Check if the user is authorized to kick students
        if ($user->usertype !== 'teacher' && $user->usertype !== 'admin') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers and admins can kick students from a class.'
            ], 403);
        }
    
        // Check if the student is part of the class
        $joinClass = joinclass::where('class_id', $class_id)->where('user_id', $student_id)->first();
    
        if (!$joinClass) {
            return response()->json([
                'error' => 'Student is not part of this class.'
            ], 404);
        }
    
        // Delete the joinclass record to kick the student
        $joinClass->delete();
    
        return response()->json([
            'message' => 'Student kicked from the class successfully.',
            'student_id' => $student_id,
        ], 200);
    }
    


    public function listStudentsInClassGendertotal(Request $request, $class_id)
    {
        // Validate the class_id parameter
        $request->validate([
            'class_id' => 'required|exists:tblclass,id'
        ]);
    
        // Get the authenticated user
        $user = $request->user();
    
        // Check if the user is authorized
        if ($user->usertype !== 'teacher' && $user->usertype !== 'admin') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers and admins can view students in a class.'
            ], 403);
        }
    
        // Fetch student details
        $students = DB::table('users')
                    ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                    ->where('joinclass.class_id', $class_id)
                    ->where('users.usertype', 'student')
                    ->select('users.id', 'users.idnumber', 'users.fname', 'users.sex')
                    ->get();
    
        // Count total students, male and female
        $genderCounts = [
            'total' => $students->count(),
            'male' => $students->where('sex', 'male')->count(),
            'female' => $students->where('sex', 'female')->count(),
        ];
    
        return response()->json([ 'gender_counts' => $genderCounts], 200);
    }


    // Method to approve a student's request to join a class
    public function approveStudentJoinRequest(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:tblclass,id',
            'user_id' => 'required|exists:users,id'
        ]);

        $user = $request->user();

        if ($user->usertype !== 'teacher' && $user->usertype !== 'admin') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers and admins can approve join requests.'
            ], 403);
        }

        $joinClass = joinclass::where('class_id', $request->class_id)
                    ->where('user_id', $request->user_id)
                    ->first();

        if (!$joinClass) {
            return response()->json([
                'error' => 'Join request not found.'
            ], 404);
        }

        $class = tblclass::find($request->class_id);

        if (!$class || $class->user_id !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized: You do not have permission to approve this join request.'
            ], 403);
        }

        $joinClass->status = 1; // Approving the join request
        $joinClass->save();

        return response()->json([
            'message' => 'Student join request approved successfully.'
        ], 200);
    }

    // Method to list all students with approved join requests in a class WITH total_students , MALE FEMALE
    public function listStudentsInClass2(Request $request, $class_id)
    {
        $request->validate([
            'class_id' => 'required|exists:tblclass,id'
        ]);
    
        $user = $request->user();
    
        if ($user->usertype !== 'teacher' && $user->usertype !== 'admin') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers and admins can view students in a class.'
            ], 403);
        }
    
        // Retrieve the list of students in the class
        $students = DB::table('users')
                    ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                    ->where('joinclass.class_id', $class_id)
                    ->where('joinclass.status', 1)
                    ->where('users.usertype', 'student')
                    ->select('users.id', 'users.idnumber', 'users.fname', 'joinclass.status')
                    ->get();
    
        // Count total students
        $totalStudents = $students->count();
    
        // Count male students
        $maleCount = DB::table('users')
                    ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                    ->where('joinclass.class_id', $class_id)
                    ->where('joinclass.status', 1)
                    ->where('users.usertype', 'student')
                    ->where('users.gender', 'male') // Adjust 'gender' field as necessary
                    ->count();
    
        // Count female students
        $femaleCount = DB::table('users')
                    ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                    ->where('joinclass.class_id', $class_id)
                    ->where('joinclass.status', 1)
                    ->where('users.usertype', 'student')
                    ->where('users.gender', 'female') // Adjust 'gender' field as necessary
                    ->count();
    
        // Prepare response with counts and students list
        $response = [
            'total_students' => $totalStudents,
            'male_count' => $maleCount,
            'female_count' => $femaleCount,
            'students' => $students
        ];
    
        return response()->json($response, 200);
    }
    

    public function listStudentsInClass3(Request $request, $class_id)
    {
        $user = $request->user();
    
        // Check user authorization
        if ($user->usertype !== 'teacher' && $user->usertype !== 'admin') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers and admins can view students in a class.'
            ], 403);
        }
    
        // Fetch unique students with pending status based on idnumber
        $students = DB::table('users')
                    ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                    ->join('tblstudent', 'users.id', '=', 'tblstudent.user_id')
                    ->join('tblstrand', 'tblstudent.strand_id', '=', 'tblstrand.id')
                    ->join('tblsection', 'tblstudent.section_id', '=', 'tblsection.id')
                    ->where('joinclass.class_id', $class_id)
                    ->where('joinclass.status', 0)
                    ->where('users.usertype', 'student')
                    ->select('users.id', 'users.idnumber', 'users.fname', 'users.mname', 'users.lname', 'joinclass.status','tblstrand.addstrand','tblstrand.grade_level','tblsection.section')
                    ->distinct('users.idnumber') // Ensure distinct idnumber
                    ->get();
    
        return response()->json($students, 200);
    }
    
    public function viewAllApprovedStudents(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Ensure the user is a teacher or has the correct permissions
        if ($user->usertype !== 'teacher') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers can view this list.'
            ], 403);
        }

        // Fetch students who are approved (status = 1)
    //    $approvedStudents = DB::table('joinclass')
        //    ->join('users', 'joinclass.user_id', '=', 'users.id')
         //   ->where('joinclass.class_id', $request->class_id)
         //   ->where('joinclass.status', 1) // Status 1 means approved
          //  ->select('users.id', 'users.email')
          //  ->get();


            $approvedStudents = tblstudent::with(['user', 'strands', 'section'])
            ->join('users', 'tblstudent.user_id', '=', 'users.id')
            ->join('joinclass', 'tblstudent.user_id', '=', 'joinclass.user_id')
            ->where('joinclass.class_id', $request->class_id)
            ->where('joinclass.status', 1) // Status 1 means approved
            ->where('users.usertype', 'student')
            ->orderBy('users.lname', 'asc')
            ->select('tblstudent.*', 'users.id as user_id', 'users.idnumber', 'users.fname', 'users.sex') // Include additional fields
            ->get();

            $genderCounts = [
                'total' => $approvedStudents->count(),
                'male' => $approvedStudents->where('sex', 'male')->count(),
                'female' => $approvedStudents->where('sex', 'female')->count(),
            ];

        return response()->json([
            'students' => $approvedStudents,
            'gender' => $genderCounts
        ], 200);
    }

    public function getStudentClassroomDetails(Request $request, $class_id)
    {
        // Authenticate the user and get the user instance
        $user = auth()->user();

        // Check if the user is a student
        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can access this class.'], 403);
        }

        // Get the class details for the provided class_id
        $class = DB::table('tblclass')->where('id', $class_id)->first();

        // Check if the class exists
        if (!$class) {
            return response()->json(['error' => 'Class not found.'], 404);
        }

        // Fetch the student’s joinclass data to ensure they are enrolled
        $joinClass = DB::table('joinclass')
            ->where('class_id', $class_id)
            ->where('user_id', $user->id)
            ->where('status', 1) // Status 1 means the student is approved
            ->first();

        // If the student is not part of the class, return an error
        if (!$joinClass) {
            return response()->json(['error' => 'You are not enrolled in this class or approval is pending.'], 403);
        }

        // Return class details to the frontend
        return response()->json($class, 200);
    }
}
