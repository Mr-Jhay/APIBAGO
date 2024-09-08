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

    // Check if a curriculum with the same name already exists
    $existingCurriculum = strandcuriculum::where('Namecuriculum', $validatedData['Namecuriculum'])->first();

    if ($existingCurriculum) {
        // If the curriculum already exists, return a conflict response (409 status)
        return response()->json([
            'message' => 'A Strand Curriculum with this name already exists!'
        ], 409);
    }

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

    public function updatecuri(Request $request, $id)
{
    $validatedData = $request->validate([
        'Namecuriculum' => 'required|string|max:255',
    ]);

    $strandCuriculum = strandcuriculum::findOrFail($id);
    $strandCuriculum->update([
        'Namecuriculum' => $validatedData['Namecuriculum'],
    ]);

    return response()->json([
        'message' => 'Strand Curriculum updated successfully!',
        'data' => $strandCuriculum
    ], 200);
}

public function deletecuri($id)
{
    $strandCuriculum = strandcuriculum::findOrFail($id);
    $strandCuriculum->delete();

    return response()->json([
        'message' => 'Strand Curriculum deleted successfully!'
    ], 200);
}

}
