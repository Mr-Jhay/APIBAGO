<?php

namespace App\Http\Controllers;

use App\Models\tblsemester;
use Illuminate\Http\Request;

class tblsemesterController extends Controller
{
    public function addsemester(Request $request)
    {
        $validated = $request->validate([
            'sem' => 'required|string|max:255',
        ]);

        $semester = tblsemester::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Semester created successfully!',
            'data' => $semester,
        ], 201);
    }

    public function viewsemester()
    {
        $semesters = tblsemester::all();

        return response()->json([
            'success' => true,
            'message' => 'Semesters retrieved successfully',
            'data' => $semesters
        ], 200);
    }

    public function updatesemester($id, Request $request)
    {
        $validated = $request->validate([
            'sem' => 'required|string|max:255',
        ]);

        $semester = tblsemester::findOrFail($id);
        $semester->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Semester updated successfully',
            'data' => $semester
        ], 200);
    }

    public function deletesemester($id)
    {
        $semester = tblsemester::findOrFail($id);
        $semester->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semester deleted successfully'
        ], 200);
    }
}
