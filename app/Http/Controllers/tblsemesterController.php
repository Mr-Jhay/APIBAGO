<?php

namespace App\Http\Controllers;

use App\Models\tblsemester;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class tblsemesterController extends Controller
{
    public function addsemester(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'sem' => 'required|string|max:255',
        ]);

        // Create a new semester record
        $semester = Semester::create($validated);

        // Return a JSON response
        return response()->json([
            'success' => true,
            'message' => 'Semester created successfully!',
            'data' => $semester,
        ], 201);
    }

    /**
     * Display a listing of all semesters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewsemester()
    {
        // Retrieve all semester records from the database
        $semesters = Semester::all();

        // Return a JSON response with the list of semesters
        return response()->json([
            'success' => true,
            'message' => 'Semesters retrieved successfully',
            'data' => $semesters
        ], 200);
    }
}
