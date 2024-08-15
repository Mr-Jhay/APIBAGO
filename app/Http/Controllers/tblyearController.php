<?php

namespace App\Http\Controllers;

use App\Models\tblyear;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class tblyearController extends Controller
{
    public function addyear(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'addyear' => 'required|string|max:255',
        ]);

        // Create a new year record
        $year = tblyear::create([
            'addyear' => $request->input('addyear'),
        ]);

        // Return a JSON response
        return response()->json([
            'message' => 'Year created successfully!',
            'data' => $year,
        ], 201);
    }

    public function viewyear()
    {
        // Retrieve all year records from the database
        $years = tblyear::all();

        // Return a JSON response with the list of years and a 200 (OK) status code
        return response()->json([
            'success' => true,
            'message' => 'Years retrieved successfully',
            'data' => $years
        ], 200);
    }
}
