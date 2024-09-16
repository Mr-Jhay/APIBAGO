<?php

namespace App\Http\Controllers;

use App\Models\joinclass;
use App\Models\tblclass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationMail;
use Illuminate\Support\Facades\Auth;

class joinclassController extends Controller
{
    // Method for students to join a class using class_id and gen_code
    public function jcstudent(Request $request)
    {
        $request->validate([
            
            'status' => 'nullable|integer',
            'gen_code' => 'required|string'
        ]);

        $user = auth()->user();

        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can join classes.'
            ], 403);
        }

        $class = DB::table('tblclass')->where('id', $request->input('class_id'))->first();

        if (!$class || $class->gen_code !== $request->input('gen_code')) {
            return response()->json([
                'error' => 'Invalid class or gen_code does not match.'
            ], 400);
        }

        $joinClass = joinclass::create([
            'user_id' => $user->id,
            'class_id' => $request->input('class_id'),
            'status' => $request->input('status', 0)
        ]);

        return response()->json($joinClass, 201);
    }

    // Method for adding students to a class without gen_code
    public function addwocode(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'nullable|integer',
        ]);

        $user = User::find($request->input('user_id'));

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $teacher = auth()->user();
        $class = DB::table('tblclass')->where('teacher_id', $teacher->id)->first();

        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        $status = ($user->usertype === 'student') ? 0 : $request->input('status');

        $joinClass = joinclass::create([
            'user_id' => $request->input('user_id'),
            'class_id' => $class->id,
            'status' => $status,
        ]);

        if ($user->usertype === 'student') {
            Mail::to($user->email)->send(new InvitationMail($class, $user));
        }

        return response()->json($joinClass, 201);
    }

    // Method for students to join a class using gen_code only
    public function jcstudent2(Request $request)
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
                    ->select('users.id','users.idnumber', 'users.fname', 'user.sex','users.email')
                    ->get();

        return response()->json($students, 200);
    }




    public function listStudentsInClassGendertotal(Request $request, $class_id)
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

    // Fetch student details
    $students = DB::table('users')
                ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                ->where('joinclass.class_id', $class_id)
                ->where('users.usertype', 'student')
                ->select('users.id', 'users.idnumber', 'users.fname', 'users.sex', 'users.email')
                ->get();

    // Count total students, male and female
    $genderCounts = [
        'total' => $students->count(),
        'male' => $students->where('sex', 'male')->count(),
        'female' => $students->where('sex', 'female')->count(),
    ];

    return response()->json([
       // 'students' => $students,
        'gender_counts' => $genderCounts
    ], 200);
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
                    ->select('users.id', 'users.idnumber', 'users.fname', 'users.email', 'joinclass.status')
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
    

    // Method to list all students with pending join requests in a class
    public function listStudentsInClass3(Request $request, $class_id)
    {
        $user = $request->user();
    
        if ($user->usertype !== 'teacher' && $user.usertype !== 'admin') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers and admins can view students in a class.'
            ], 403);
        }
    
        // Fetch students with pending status
        $students = DB::table('users')
                    ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                    ->where('joinclass.class_id', $class_id)
                    ->where('joinclass.status', 0)
                    ->where('users.usertype', 'student')
                    ->select('users.id', 'users.idnumber', 'users.fname', 'users.email', 'joinclass.status')
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
        $approvedStudents = DB::table('joinclass')
            ->join('users', 'joinclass.user_id', '=', 'users.id')
            ->where('joinclass.class_id', $request->class_id)
            ->where('joinclass.status', 1) // Status 1 means approved
            ->select('users.id', 'users.email')
            ->get();

        return response()->json(['students' => $approvedStudents], 200);
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
