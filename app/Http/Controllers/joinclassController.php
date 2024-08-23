<?php

namespace App\Http\Controllers;

use App\Models\joinclass;
use App\Models\User;
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

    // Retrieve the authenticated user or use the provided user_id
    $user = User::find($request->input('user_id'));

    if (!$user) {
        return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
    }

    // Assume the teacher is authenticated and assign the class_id based on the teacher's context
    $teacher = auth()->user(); // Assuming the teacher is logged in

    // Retrieve the class_id where the teacher is assigned
    $class = \DB::table('tblclass')->where('teacher_id', $teacher->id)->first();

    if (!$class) {
        return response()->json(['error' => 'Class not found'], Response::HTTP_NOT_FOUND);
    }

    // Default status if the user is a student
    $status = ($user->usertype === 'student') ? 1 : $request->input('status');

    // Create the join class record
    $joinClass = joinclass::create([
        'user_id' => $request->input('user_id'),
        'class_id' => $class->id,  // Use the class_id from the teacher's context
        'status' => $status,
    ]);

    // Return response
    return response()->json($joinClass, Response::HTTP_CREATED);
}

    

}
