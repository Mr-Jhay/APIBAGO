<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\InvitationMail; 
use App\Models\joinclass;
use App\Models\User;
use App\Models\tblclass;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;

class joinclassController extends Controller
{
    public function jcstudent(Request $request)
    {
        // Validate the request
        $request->validate([
            'class_id' => 'required|exists:tblclass,id',
            'status' => 'nullable|integer',
            'gen_code' => 'required|string' // Validate gen_code as required and a string
        ]);
    
        // Retrieve the authenticated user
        $user = auth()->user();
    
        // Check if the authenticated user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can join classes.'
            ], 403); // HTTP Forbidden
        }
    
        // Fetch the class to check the gen_code
        $class = \DB::table('tblclass')->where('id', $request->input('class_id'))->first();
    
        // Check if the class exists and the gen_code matches
        if (!$class || $class->gen_code !== $request->input('gen_code')) {
            return response()->json([
                'error' => 'Invalid class or gen_code does not match.'
            ], 400); // HTTP Bad Request
        }
    
        // Create the joinClass record
        $joinClass = joinclass::create([
            'user_id' => $user->id, // Use the authenticated user's ID
            'class_id' => $request->input('class_id'),
            'status' => $request->input('status', 0) // Default to 0 if not provided
        ]);
    
        // Return the newly created record with a 201 status code
        return response()->json($joinClass, 201); // HTTP Created
    }

    public function addwocode(Request $request)
    {
        // Validate the request with the necessary fields
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'nullable|integer',
        ]);
    
        // Retrieve the user based on the provided user_id
        $user = User::find($request->input('user_id'));
    
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        // Assume the teacher is authenticated and assign the class_id based on the teacher's context
        $teacher = auth()->user(); // Assuming the teacher is logged in
    
        // Retrieve the class_id where the teacher is assigned
        $class = \DB::table('tblclass')->where('teacher_id', $teacher->id)->first();
    
        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }
    
        // Default status if the user is a student
        $status = ($user->usertype === 'student') ? 0 : $request->input('status');
    
        // Create the join class record
        $joinClass = joinclass::create([
            'user_id' => $request->input('user_id'),
            'class_id' => $class->id,  // Use the class_id from the teacher's context
            'status' => $status,
        ]);
    
        // Send an email invitation to the student
        if ($user->usertype === 'student') {
            Mail::to($user->email)->send(new InvitationMail($class, $user));
        }
    
        // Return response
        return response()->json($joinClass, 201);
    }

    public function jcstudent2(Request $request)
    {
        // Validate the request
        $request->validate([
            'gen_code' => 'required|string' // Validate gen_code as required and a string
        ]);
    
        // Retrieve the authenticated user
        $user = auth()->user();
    
        // Ensure the user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can join classes.'
            ], 403); // HTTP Forbidden
        }
    
        // Fetch the class using the gen_code
        $class = \DB::table('tblclass')->where('gen_code', $request->input('gen_code'))->first();
    
        // Check if the class exists
        if (!$class) {
            return response()->json([
                'error' => 'Invalid class or gen_code does not match.'
            ], 400); // HTTP Bad Request
        }
    
        // Create the joinClass record with the status automatically set to 0
        $joinClass = joinclass::create([
            'user_id' => $user->id, // Use the authenticated user's ID
            'class_id' => $class->id, // Use the found class ID
            'status' => 0 // Status automatically set to 0 indicating pending approval
        ]);
    
        // Return a response indicating that the student needs to wait for approval
        return response()->json([
            'message' => 'Request to join the class submitted. Please wait for teacher approval.'
        ], 201); // HTTP Created
    }
    



    public function addStudentToClass(Request $request)
    {
        // Validate the request
        $request->validate([
            'class_id' => 'required|exists:tblclass,id', // The ID of the class
            'user_ids' => 'required|array', // Array of student IDs
            'user_ids.*' => 'exists:users,id' // Validate that each user_id exists in the users table
        ]);
    
        // Retrieve the authenticated user (teacher)
        $user = auth()->user();
    
        // Ensure the user is a teacher
        if ($user->usertype !== 'teacher') {
            return response()->json([
                'error' => 'Unauthorized: Only teachers can approve or reject class join requests.'
            ], 403); // HTTP Forbidden
        }
    
        // Fetch the class to ensure it exists and belongs to the teacher
        $class = tblclass::find($request->input('class_id'));
    
        // Check if the class exists and belongs to the teacher
        if (!$class || $class->user_id !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized: You do not have permission to modify this class.'
            ], 403); // HTTP Forbidden
        }
    
        // Iterate over the student IDs and update their join requests
        foreach ($request->input('user_ids') as $userId) {
            // Fetch the student based on user_id
            $student = \DB::table('users')->where('id', $userId)->first();
    
            // Ensure the user is a student
            if (!$student || $student->usertype !== 'student') {
                return response()->json([
                    'error' => 'Invalid user: One or more provided user IDs do not belong to a student.'
                ], 400); // HTTP Bad Request
            }
    
            // Fetch or create the joinClass record
            $joinClass = joinclass::firstOrNew([
                'class_id' => $request->input('class_id'),
                'user_id' => $userId
            ]);
    
            // Check if the joinClass record already exists
            if ($joinClass->exists) {
                // If already exists, skip or update if needed
                continue;
            }
    
            // Create the joinClass record with status 1 (approved)
            $joinClass->status = 1; // Status 1 for 'accepted'
            $joinClass->save();
        }
    
        // Return a response indicating the update
        return response()->json([
            'message' => 'Student requests status updated successfully.'
        ], 200); // HTTP OK
    }
    
    

public function listStudentsInClass(Request $request, $class_id)
{
    // Validate the class_id
    $request->validate([
        'class_id' => 'required|exists:tblclass,id' // Ensure class_id exists in the tblclass table
    ]);

    // Retrieve the authenticated user
    $user = $request->user();

    // Ensure the user is authorized to view students in the class
    if ($user->usertype !== 'teacher' && $user->usertype !== 'admin') {
        return response()->json([
            'error' => 'Unauthorized: Only teachers and admins can view students in a class.'
        ], 403); // HTTP Forbidden
    }

    // Retrieve students enrolled in the specified class
    $students = \DB::table('users') // Use the users table
                   ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                   ->where('joinclass.class_id', $class_id)
                   ->where('users.user_type', 'student') // Filter to include only students
                   ->select('users.id','users.idnumber', 'users.fname', 'users.email')
                   ->get();

    // Return the list of students
    return response()->json($students, 200); // HTTP OK
}

public function approveStudentJoinRequest(Request $request)
{
    // Validate the request
    $request->validate([
        'class_id' => 'required|exists:tblclass,id', // Ensure class_id exists in the tblclass table
        'user_id' => 'required|exists:users,id' // Ensure user_id exists in the users table
    ]);

    // Retrieve the authenticated user
    $user = $request->user();

    // Ensure the user is a teacher or admin
    if ($user->usertype !== 'teacher' && $user->usertype !== 'admin') {
        return response()->json([
            'error' => 'Unauthorized: Only teachers and admins can approve join requests.'
        ], 403); // HTTP Forbidden
    }

    // Fetch the joinClass record
    $joinClass = \DB::table('joinclass')
                    ->where('class_id', $request->input('class_id'))
                    ->where('user_id', $request->input('user_id'))
                    ->first();

    // Check if the joinClass record exists
    if (!$joinClass) {
        return response()->json([
            'error' => 'Join request not found.'
        ], 404); // HTTP Not Found
    }

    // Fetch the class to ensure it belongs to the teacher
    $class = tblclass::find($request->input('class_id'));

    // Check if the class exists and belongs to the teacher
    if (!$class || $class->user_id !== $user->id) {
        return response()->json([
            'error' => 'Unauthorized: You do not have permission to approve this join request.'
        ], 403); // HTTP Forbidden
    }

    // Update the status of the joinClass record to 'approved'
    \DB::table('joinclass')
        ->where('class_id', $request->input('class_id'))
        ->where('user_id', $request->input('user_id'))
        ->update(['status' => 1]); // Status 1 for 'approved'

    // Return a response indicating the update
    return response()->json([
        'message' => 'Student join request approved successfully.'
    ], 200); // HTTP OK
}








}
