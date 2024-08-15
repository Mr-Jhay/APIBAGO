<?php

namespace App\Http\Controllers;

use App\Models\tblsection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class tblsectionController extends Controller
{
    public function addsection(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'section' => 'required|string|max:255',
        ]);

        // Create a new section record
        $section = tblsection::create($validated);

        // Return a JSON response
        return response()->json([
            'success' => true,
            'message' => 'Section created successfully!',
            'data' => $section,
        ], 201);
    }

    /**
     * Display a listing of all sections.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewsection()
    {
        // Retrieve all section records from the database
        $sections = tblsection::all();

        // Return a JSON response with the list of sections
        return response()->json([
            'success' => true,
            'message' => 'Sections retrieved successfully',
            'data' => $sections
        ], 200);
    }
}
