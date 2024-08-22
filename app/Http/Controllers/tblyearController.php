<?php

namespace App\Http\Controllers;

use App\Models\tblyear; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class tblyearController extends Controller
{
    // Method to add a new year (already exists)
    public function addyear(Request $request)
    {
        $request->validate([
            'addyear' => 'required|string|max:255',
        ]);

        $year = tblyear::create([
            'addyear' => $request->input('addyear'),
        ]);

        return response()->json([
            'message' => 'Year created successfully!',
            'data' => $year,
        ], 201);
    }

    // Method to view all years (already exists)
    public function viewyear()
    {
        $years = tblyear::all();

        return response()->json([
            'success' => true,
            'message' => 'Years retrieved successfully',
            'data' => $years
        ], 200);
    }

    // Method to update an existing year
    public function updateyear(Request $request, $id)
    {
        $request->validate([
            'addyear' => 'required|string|max:255',
        ]);

        // Find the year record
        $year = tblyear::findOrFail($id);

        // Update the year field
        $year->update([
            'addyear' => $request->input('addyear'),
        ]);

        return response()->json([
            'message' => 'Year updated successfully!',
            'data' => $year,
        ], 200);
    }

    // Method to delete a year
    public function deleteyear($id)
    {
        // Find the year record
        $year = tblyear::findOrFail($id);

        // Delete the year
        $year->delete();

        return response()->json([
            'message' => 'Year deleted successfully!',
        ], 200);
    }
}
