<?php

namespace App\Http\Controllers;

use App\Models\tblsubject;
use Illuminate\Http\Request;

class tblsubjectController extends Controller
{
    public function addsubject(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'subjectname' => 'required|string|max:255',
        ]);
    
        // Check for duplicate subject names
        $existingSubject = tblsubject::where('subjectname', $validated['subjectname'])->first();
        if ($existingSubject) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate subject name detected',
            ], 409); // 409 Conflict status code
        }
    
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
        $subjects = tblsubject::all();

        return response()->json([
            'success' => true,
            'message' => 'Subjects retrieved successfully',
            'data' => $subjects
        ], 200);
    }

    public function updatesubject(Request $request, $id)
    {
        $validated = $request->validate([
            'subjectname' => 'required|string|max:255',
        ]);

        $subject = tblsubject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        $subject->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => $subject
        ], 200);
    }

    public function deletesubject($id)
    {
        $subject = tblsubject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully'
        ], 200);
    }
}
