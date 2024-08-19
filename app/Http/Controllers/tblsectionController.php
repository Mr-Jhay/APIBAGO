<?php

namespace App\Http\Controllers;

use App\Models\tblsection;
use App\Models\tblstrand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class tblsectionController extends Controller
{
    public function addsection(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'strand_id' => 'required|exists:tblstrand,id',
            'section' => 'required|string|max:255',
        ]);

        // Create a new section
        $section = tblsection::create([
            'strand_id' => $validated['strand_id'],
            'section' => $validated['section'],
        ]);

        // Return a response (e.g., the created section or a success message)
        return response()->json([
            'message' => 'Section created successfully!',
            'section' => $section,
        ], 201);
    }

   
    public function viewsection()
    {
        // Retrieve all sections with their associated strand
        $sections = Tblsection::with('strand')->get();

        // Return a response with the list of sections and strand details
        return response()->json([
            'message' => 'Sections retrieved successfully!',
            'sections' => $sections,
        ], 200);
    }

    public function updatesection(Request $request, $id)
    {
        // Validate the request data
        $validated = $request->validate([
            'strand_id' => 'required|exists:tblstrand,id',
            'section' => 'required|string|max:255',
        ]);

        // Find the section by ID
        $section = Tblsection::findOrFail($id);

        // Update the section's data
        $section->update([
            'strand_id' => $validated['strand_id'],
            'section' => $validated['section'],
        ]);

        // Return a response (e.g., the updated section or a success message)
        return response()->json([
            'message' => 'Section updated successfully!',
            'section' => $section,
        ], 200);
    }

    public function deletesection($id)
    {
        $section = tblsection::findOrFail($id);
        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully!',
        ], 200);
    }
}
