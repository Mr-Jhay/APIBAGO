<?php

namespace App\Http\Controllers;

use App\Models\strandcuriculum;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class strandcuriculumController extends Controller
{
    public function addcuri(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'Namecuriculum' => 'required|string|max:255',
        ]);

        // Create a new StrandCuriculum record
        $strandCuriculum = strandcuriculum::create([
            'Namecuriculum' => $validatedData['Namecuriculum'],
        ]);

        // Return a JSON response with the created record
        return response()->json([
            'message' => 'Strand Curriculum created successfully!',
            'data' => $strandCuriculum
        ], 201);
    }

    public function viewcuri()
    {
        // Retrieve all records from the strandcuriculum table
        $strandCuriculums = strandcuriculum::all();

        return response()->json([
            'data' => $strandCuriculums
        ], 200);
    }
}
