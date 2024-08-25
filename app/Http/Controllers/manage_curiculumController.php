<?php

namespace App\Http\Controllers;

use App\Models\manage_curiculum;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator; 
use App\Models\strandcuriculum;
use App\Models\tblsubject;
use App\Models\tblstrand;


class manage_curiculumController extends Controller
{
    public function addcuriculum(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'scuriculum_id' => 'required|exists:strandcuriculum,id', 
            'subject_ids' => 'required|array', 
            'subject_ids.*' => 'exists:tblsubject,id', 
            'strand_id' => 'required|exists:tblstrand,id',
            'semester' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $createdEntries = [];

        // Loop through each subject ID and create a new record for each
        foreach ($request->subject_ids as $subject_id) {
            $manageCuriculum = manage_curiculum::create([
                'scuriculum_id' => $request->scuriculum_id,
                'subject_id' => $subject_id,
                'strand_id' => $request->strand_id,
                'semester' => $request->semester,
            ]);

            $createdEntries[] = $manageCuriculum;
        }

        return response()->json(['message' => 'Curriculum entries created successfully', 'data' => $createdEntries], 201);
    }

 //   public function viewcuriculum()
//    {
        // Retrieve all records from the manage_curiculum table
 //       $curriculums = manage_curiculum::all();

   //     return response()->json(['data' => $curriculums], 200);
   // }

    public function viewcuriculum()
    {
        // Retrieve all records from the manage_curiculum table with related data
        $curriculums = manage_curiculum::select(
            'manage_curiculum.id',
            'strandcuriculum.Namecuriculum',  // Field from strandcuriculum table
            'tblsubject.subjectname',  // Field from tblsubject table
            'tblstrand.addstrand',  // Field from tblstrand table
            'tblstrand.grade_level',  // Field from tblstrand table
            'manage_curiculum.semester'
        )
        ->join('strandcuriculum', 'manage_curiculum.scuriculum_id', '=', 'strandcuriculum.id')
        ->join('tblsubject', 'manage_curiculum.subject_id', '=', 'tblsubject.id')
        ->join('tblstrand', 'manage_curiculum.strand_id', '=', 'tblstrand.id')
        ->get();
    
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

    public function updateCuriculum(Request $request, $id)
{
    // Validate the incoming request data
    $validator = Validator::make($request->all(), [
        'scuriculum_id' => 'required|exists:strandcuriculum,id', 
        'subject_ids' => 'required|array', 
        'subject_ids.*' => 'exists:tblsubject,id', 
        'strand_id' => 'required|exists:tblstrand,id',
        'semester' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Find the record by ID and delete old entries
    manage_curiculum::where('scuriculum_id', $request->scuriculum_id)
        ->where('strand_id', $request->strand_id)
        ->where('semester', $request->semester)
        ->delete();

    $updatedEntries = [];

    // Loop through each subject ID and create a new record for each
    foreach ($request->subject_ids as $subject_id) {
        $manageCuriculum = manage_curiculum::create([
            'scuriculum_id' => $request->scuriculum_id,
            'subject_id' => $subject_id,
            'strand_id' => $request->strand_id,
            'semester' => $request->semester,
        ]);

        $updatedEntries[] = $manageCuriculum;
    }

    return response()->json(['message' => 'Curriculum entries updated successfully', 'data' => $updatedEntries], 200);
}

public function deleteCuriculum(Request $request)
{
    // Validate the incoming request data
    $validator = Validator::make($request->all(), [
        'scuriculum_id' => 'required|exists:strandcuriculum,id', 
        'strand_id' => 'required|exists:tblstrand,id',
        'semester' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Soft delete all curriculum entries that match the criteria
    manage_curiculum::where('scuriculum_id', $request->scuriculum_id)
        ->where('strand_id', $request->strand_id)
        ->where('semester', $request->semester)
        ->delete(); // Soft delete the matching records

    return response()->json(['message' => 'Curriculum entries deleted successfully'], 200);
}



}
