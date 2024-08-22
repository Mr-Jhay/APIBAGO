<?php

namespace App\Http\Controllers;

use App\Models\tblyear;
use Illuminate\Http\Request;

class tblyearController extends Controller
{
    public function addyear(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'addyear' => 'required|string|max:255',
        ]);

        // Check for duplicate year names
        $existingYear = tblyear::where('addyear', $validated['addyear'])->first();
        if ($existingYear) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate year detected',
            ], 409); // 409 Conflict status code
        }

        // Create a new year record with the validated data
        $year = tblyear::create($validated);

        // Return a JSON response with the created year and a 201 (Created) status code
        return response()->json([
            'success' => true,
            'message' => 'Year created successfully',
            'data' => $year
        ], 201);
    }

    public function viewyear()
    {
        $years = tblyear::all();

        return response()->json([
            'success' => true,
            'message' => 'Years retrieved successfully',
            'data' => $years
        ], 200);
    }

    public function updateyear(Request $request, $id)
    {
        $validated = $request->validate([
            'addyear' => 'required|string|max:255',
        ]);

        $year = tblyear::find($id);

        if (!$year) {
            return response()->json([
                'success' => false,
                'message' => 'Year not found'
            ], 404);
        }

        // Check for duplicate year names
        $existingYear = tblyear::where('addyear', $validated['addyear'])->where('id', '!=', $id)->first();
        if ($existingYear) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate year detected',
            ], 409); // 409 Conflict status code
        }

        $year->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Year updated successfully',
            'data' => $year
        ], 200);
    }

    public function deleteyear($id)
    {
        $year = tblyear::find($id);

        if (!$year) {
            return response()->json([
                'success' => false,
                'message' => 'Year not found'
            ], 404);
        }

        $year->delete();

        return response()->json([
            'success' => true,
            'message' => 'Year deleted successfully'
        ], 200);
    }
}
