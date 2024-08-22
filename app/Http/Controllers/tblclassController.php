<?php

namespace App\Http\Controllers;

use App\Models\tblclass;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class tblclassController extends Controller
{
    public function addclass(Request $request)
{
    // Validate the incoming request
    $validatedData = $request->validate([
        'strand_id' => 'required|exists:tblstrand,id',
        'section_id' => 'required|exists:tblsection,id',
        'subject_id' => 'required|exists:manage_curiculum,id',
        'class_desc' => 'nullable|string',
        'profile_img' => 'nullable|string',
        'gen_code' => 'required|string',
    ]);

    // Retrieve the currently authenticated user
    $user = $request->user();

    // Check if the user has the 'teacher' usertype
    if ($user && $user->usertype === 'teacher') {
        // Create a new TblClass record with the authenticated user's ID
        $class = tblclass::create(array_merge($validatedData, ['user_id' => $user->id]));

        // Return a JSON response with success message and created record
        return response()->json([
            'message' => 'Class created successfully.',
            'data' => $class
        ], 201); // HTTP status code 201 Created
    } else {
        // Return a JSON response with an error message
        return response()->json([
            'message' => 'You do not have the required privileges to create a class.'
        ], 403); // HTTP status code 403 Forbidden
    }
}

//List All Classes Created by the Authenticated Teacher
public function allclasses(Request $request)
{
    // Retrieve the currently authenticated user
    $user = $request->user();

    // Ensure the user is authenticated and is a teacher
    if ($user && $user->usertype === 'teacher') {
        // Get all classes created by the authenticated teacher
        $classes = tblclass::where('user_id', $user->id)->get();

        // Return a JSON response with the list of classes
        return response()->json([
            'data' => $classes
        ], 200); // HTTP status code 200 OK
    } else {
        // Return a JSON response with an error message
        return response()->json([
            'message' => 'Unauthorized access. Only teachers can view their classes.'
        ], 403); // HTTP status code 403 Forbidden
    }
}


//Show (Display a Specific Class Created by the Authenticated Teacher)
public function showclass(Request $request, $id)
{
    // Retrieve the currently authenticated user
    $user = $request->user();

    // Ensure the user is authenticated and is a teacher
    if ($user && $user->usertype === 'teacher') {
        // Find the class by ID and ensure it belongs to the authenticated teacher
        $class = tblclass::where('id', $id)->where('user_id', $user->id)->first();

        if ($class) {
            // Return a JSON response with the class data
            return response()->json([
                'data' => $class
            ], 200); // HTTP status code 200 OK
        } else {
            // Return a JSON response with an error message if the class is not found
            return response()->json([
                'message' => 'Class not found or you do not have permission to view this class.'
            ], 404); // HTTP status code 404 Not Found
        }
    } else {
        // Return a JSON response with an error message if unauthorized
        return response()->json([
            'message' => 'Unauthorized access. Only teachers can view their classes.'
        ], 403); // HTTP status code 403 Forbidden
    }
}


}
