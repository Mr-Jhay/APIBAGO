<?php

namespace App\Http\Controllers;

use App\Models\tblsubject;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class tblsubjectController extends Controller
{
    public function addsubject(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'subjectname' => 'required|string|max:255', // 'subjectname' is required, must be a string, and up to 255 characters
        ]);

        // Create a new subject record with the validated data
        $subject = tblsubject::create($validated);

        // Return a JSON response with the created subject and a 201 (Created) status code
        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => $subject
        ], 201);
    }

    public function viewsubject()
    {
        // Retrieve all subjects from the database
        $subjects = tblsubject::all();

        // Return a JSON response with the list of subjects and a 200 (OK) status code
        return response()->json([
            'success' => true,
            'message' => 'Subjects retrieved successfully',
            'data' => $subjects
        ], 200);
    }
}
