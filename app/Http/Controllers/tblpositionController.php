<?php

namespace App\Http\Controllers;

use App\Models\tblposition;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class tblpositionController extends Controller
{
    public function addposition(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'teacher_postion' => 'required|string|max:255',
        ]);

        // Create a new position record
        $position = tblposition::create($validated);

        // Return a JSON response
        return response()->json([
            'success' => true,
            'message' => 'Position created successfully!',
            'data' => $position,
        ], 201);
    }

    /**
     * Display a listing of all positions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewposition()
    {
        // Retrieve all position records from the database
        $positions = tblposition::all();

        // Return a JSON response with the list of positions
        return response()->json([
            'success' => true,
            'message' => 'Positions retrieved successfully',
            'data' => $positions
        ], 200);
    }
}
