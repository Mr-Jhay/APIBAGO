<?php

namespace App\Http\Controllers;

use App\Models\tblsection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class tblsectionController extends Controller
{
    public function addsection(Request $request)
    {
        $validated = $request->validate([
            'section' => 'required|string|max:255',
        ]);

        $section = tblsection::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully!',
            'data' => $section,
        ], 201);
    }

    public function viewsection()
    {
        $sections = tblsection::all();

        return response()->json([
            'success' => true,
            'message' => 'Sections retrieved successfully',
            'data' => $sections
        ], 200);
    }

    public function updatesection(Request $request, $id)
    {
        $validated = $request->validate([
            'section' => 'required|string|max:255',
        ]);

        $section = tblsection::findOrFail($id);
        $section->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully!',
            'data' => $section,
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
