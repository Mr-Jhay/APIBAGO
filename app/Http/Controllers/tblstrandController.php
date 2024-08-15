<?php

namespace App\Http\Controllers;

use App\Models\tblstrand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class tblstrandController extends Controller
{
    public function addstrand(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'addstrand' => 'required|string|max:255',
        ]);

        // Create a new strand record
        $strand = tblstrand::create($validated);

        // Return a JSON response
        return response()->json([
            'success' => true,
            'message' => 'Strand created successfully!',
            'data' => $strand,
        ], 201);
    }

    /**
     * Display a listing of all strands.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewstrand()
    {
        // Retrieve all strand records from the database
        $strands = tblstrand::all();

        // Return a JSON response with the list of strands
        return response()->json([
            'success' => true,
            'message' => 'Strands retrieved successfully',
            'data' => $strands
        ], 200);
    }
}
