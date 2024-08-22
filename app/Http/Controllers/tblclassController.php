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

}
