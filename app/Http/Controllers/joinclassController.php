<?php

namespace App\Http\Controllers;

use App\Models\joinclass;
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
        $joinClass = JoinClass::create([
            'user_id' => $user->id, // Use the authenticated user's ID
            'class_id' => $request->input('class_id'),
            'status' => $request->input('status', 0) // Default to 0 if not provided
        ]);
    
        // Return the newly created record with a 201 status code
        return response()->json($joinClass, 201); // HTTP Created
    }
    

}
