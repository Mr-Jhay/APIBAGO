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

    // Check if there's already an active year
    if (isset($validated['is_active']) && $validated['is_active']) {
        $activeYear = tblyear::where('is_active', true)->first();
        if ($activeYear) {
            return response()->json([
                'success' => false,
                'message' => 'An active school year already exists. Deactivate it first to activate a new one.',
            ], 409); // 409 Conflict status code
        }
    }

    // Create a new year record with the validated data
    $year = tblyear::create($validated);

    return response()->json([
        'success' => true,
        'message' => 'Year created successfully',
        'data' => $year
    ], 201);
}

public function updateyear(Request $request, $id)
{
    // Validate the incoming request data
    $validated = $request->validate([
        'addyear' => 'required|string|max:255',
        'is_active' => 'boolean' // Ensure 'is_active' is boolean
    ]);

    // Check if the year exists
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
        ], 409); // Return 409 Conflict if a duplicate year exists
    }

    // If trying to set this year as active, ensure no other year is already active
    if (isset($validated['is_active']) && $validated['is_active']) {
        $activeYear = tblyear::where('is_active', true)->where('id', '!=', $id)->first();
        if ($activeYear) {
            return response()->json([
                'success' => false,
                'message' => 'Another active school year already exists. Deactivate it first to activate a new one.',
            ], 409); // Return 409 Conflict if another year is already active
        }
    }

    // Deactivate other years if this one is marked as active
    if (isset($validated['is_active']) && $validated['is_active']) {
        tblyear::where('is_active', true)->update(['is_active' => false]); // Deactivate all other years
    }

    // Update the school year record with the validated data
    $year->update($validated);

    return response()->json([
        'success' => true,
        'message' => 'Year updated successfully',
        'data' => $year
    ], 200);
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
// Update an existing school year


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
