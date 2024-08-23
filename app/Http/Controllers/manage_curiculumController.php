<?php

namespace App\Http\Controllers;

use App\Models\manage_curiculum;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;

class manage_curiculumController extends Controller
{
    public function addcuriculum(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'scuriculum_id' => 'required|exists:strandcuriculum,id', //strandcuriculum na table ito yung pinadagdag mo
            'subject_id' => 'required|exists:tblsubject,id', //id nung subject sa tblsubject
            'strand_id' => 'required|exists:tblstrand,id',//tblstrand na table id niya 
            'semester' => 'required|string|max:255',//ikaw mismo mag insert dito drop down ito 1st sem or second sem
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

    public function updateStatus(Request $request, $id)
    {
        // Validate the incoming request data
        $request->validate([
            'status' => 'required|integer|in:0,1', // Ensure status is either 0=not active or 1=active
        ]);

        // Find the record by ID
        $manageCuriculum = manage_curiculum::findOrFail($id);

        // Update the status column
        $manageCuriculum->status = $request->input('status');
        $manageCuriculum->save();

        // Return a response, e.g., success message or updated record
        return response()->json([
            'message' => 'Status updated successfully!',
            'data' => $manageCuriculum,
        ], 200);
    }
}
