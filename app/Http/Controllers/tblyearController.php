<?php

namespace App\Http\Controllers;

use App\Models\tblyear;
use Illuminate\Http\Request;

class tblyearController extends Controller
{
    // Add a new school year
    public function addyear(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'addyear' => 'required|string|max:255',
            'is_active' => 'boolean' // Adding is_active flag for the year
        ]);

        // Check for duplicate year names
        $existingYear = tblyear::where('addyear', $validated['addyear'])->first();
        if ($existingYear) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate year detected',
            ], 409); // 409 Conflict status code
        }

        // Deactivate all other years if this one is marked as active
        if (isset($validated['is_active']) && $validated['is_active']) {
            tblyear::where('is_active', true)->update(['is_active' => false]);
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

    // View all school years
    public function viewyear()
    {
        $years = tblyear::all();

        return response()->json([
            'success' => true,
            'message' => 'Years retrieved successfully',
            'data' => $years
        ], 200);
    }

    // Update an existing school year
    public function updateyear(Request $request, $id)
    {
        $validated = $request->validate([
            'addyear' => 'required|string|max:255',
            'is_active' => 'boolean' // Handle the active flag for updating
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

        // Deactivate all other years if this one is marked as active
        if (isset($validated['is_active']) && $validated['is_active']) {
            tblyear::where('is_active', true)->update(['is_active' => false]);
        }

        $year->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Year updated successfully',
            'data' => $year
        ], 200);
    }

    // Delete a school year
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

    // Method to toggle active status for a school year
    public function toggleActive($id)
    {
        $year = tblyear::find($id);

        if (!$year) {
            return response()->json([
                'success' => false,
                'message' => 'Year not found'
            ], 404);
        }

        // If this year is being activated, deactivate others
        if (!$year->is_active) {
            tblyear::where('is_active', true)->update(['is_active' => false]);
            $year->update(['is_active' => true]);
        } else {
            $year->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Year activation status toggled successfully',
            'data' => $year
        ], 200);
    }
}
