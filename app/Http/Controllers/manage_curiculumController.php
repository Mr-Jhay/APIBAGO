<?php

namespace App\Http\Controllers;

use App\Models\manage_curiculum;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class manage_curiculumController extends Controller
{
    public function addcuriculum(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'scuriculum_id' => 'required|exists:strandcuriculum,id',
            'subject_id' => 'required|exists:tblsubject,id',
            'strand_id' => 'required|exists:tblstrand,id',
            'semester' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new record in the manage_curiculum table
        $manageCuriculum = manage_curiculum::create([
            'scuriculum_id' => $request->scuriculum_id,
            'subject_id' => $request->subject_id,
            'strand_id' => $request->strand_id,
            'semester' => $request->semester,
        ]);

        return response()->json(['message' => 'Curriculum entry created successfully', 'data' => $manageCuriculum], 201);
    }

    public function viewcuriculum()
    {
        // Retrieve all records from the manage_curiculum table
        $curriculums = manage_curiculum::all();

        return response()->json(['data' => $curriculums], 200);
    }
}
