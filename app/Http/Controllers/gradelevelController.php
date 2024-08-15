<?php

namespace App\Http\Controllers;

use App\Models\gradelevel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class gradelevelController extends Controller
{
    public function addgradelevel(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'glevel' => 'required|string|max:255',
        ]);

        // Create a new grade level record
        $gradeLevel = gradelevel::create($validated);

        // Return a JSON response
        return response()->json([
            'success' => true,
            'message' => 'Grade level created successfully!',
            'data' => $gradeLevel,
        ], 201);
    }

    /**
     * Display a listing of all grade levels.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewgradelevel()
    {
        // Retrieve all grade level records from the database
        $gradeLevels = gradelevel::all();

        // Return a JSON response with the list of grade levels
        return response()->json([
            'success' => true,
            'message' => 'Grade levels retrieved successfully',
            'data' => $gradeLevels
        ], 200);
    }
}
