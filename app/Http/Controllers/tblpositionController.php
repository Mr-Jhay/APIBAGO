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
        $validated = $request->validate([
            'teacher_postion' => 'required|string|max:255',
        ]);

        // Check for duplicate position names
        $existingPosition = tblposition::where('teacher_postion', $validated['teacher_postion'])->first();
        if ($existingPosition) {
            return response()->json([ 
                'success' => false,
                'message' => 'Duplicate teacher position detected',
            ], 409); // 409 Conflict status code
        }

        $position = tblposition::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Position created successfully!',
            'data' => $position,
        ], 201);
    }

    public function viewposition()
    {
        $positions = tblposition::all();

        return response()->json([
            'success' => true,
            'message' => 'Positions retrieved successfully',
            'data' => $positions
        ], 200);
    }

    public function updateposition(Request $request, $id)
    {
        $validated = $request->validate([
            'teacher_postion' => 'required|string|max:255',
        ]);

        // Check for duplicate position names (exclude current record)
        $existingPosition = tblposition::where('teacher_postion', $validated['teacher_postion'])
            ->where('id', '!=', $id)
            ->first();
        if ($existingPosition) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate teacher position detected',
            ], 409); // 409 Conflict status code
        }

        $position = tblposition::findOrFail($id);
        $position->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Position updated successfully!',
            'data' => $position,
        ], 200);
    }

    public function deleteposition($id)
    {
        $position = tblposition::findOrFail($id);
        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Position deleted successfully!'
        ], 200);
    }
}
