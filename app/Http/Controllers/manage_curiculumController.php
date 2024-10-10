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
use App\Models\tblsection;


class manage_curiculumController extends Controller
{
    public function addcuriculum(Request $request)
{
    // Validate the incoming request data
    $validator = Validator::make($request->all(), [
        'strand_id' => 'required|exists:tblstrand,id',
        'subject_ids' => 'required|array', 
        'subject_ids.*' => 'exists:tblsubject,id',
        'semester' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $createdEntries = [];
    $duplicateSubjects = [];

    // Loop through each subject ID and check if it already exists for the strand and semester
    foreach ($request->subject_ids as $subject_id) {
        // Check if the entry already exists
        $existingEntry = manage_curiculum::where('strand_id', $request->strand_id)
            ->where('subject_id', $subject_id)
            ->where('semester', $request->semester)
            ->exists();

        // If the entry exists, store the subject in the duplicate array, otherwise, create a new one
        if ($existingEntry) {
            $duplicateSubjects[] = $subject_id;
        } else {
            $manageCuriculum = manage_curiculum::create([
                'strand_id' => $request->strand_id,
                'subject_id' => $subject_id,
                'semester' => $request->semester,
            ]);
            $createdEntries[] = $manageCuriculum;
        }
    }

    // If there are duplicate subjects, return an error response
    if (count($duplicateSubjects) > 0) {
        return response()->json([
            'message' => 'Some subjects already exist for the selected strand and semester.',
            'duplicate_subjects' => $duplicateSubjects,
        ], 422);
    }

    // Prepare the response message for successful creation
    $message = count($createdEntries) > 0 ? 'Curriculum entries created successfully' : 'No new entries created';
    
    return response()->json([
        'message' => $message,
        'data' => $createdEntries,
    ], 201);
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
            'tblstrand.addstrand',  // Field from tblstrand table
            'tblstrand.grade_level',  // Field from tblstrand table
            'tblsubject.subjectname',  // Field from tblsubject table
            'manage_curiculum.semester'
        )
        ->join('strandcuriculum', 'manage_curiculum.scuriculum_id', '=', 'strandcuriculum.id')
        ->join('tblstrand', 'manage_curiculum.strand_id', '=', 'tblstrand.id')
        ->join('tblsubject', 'manage_curiculum.subject_id', '=', 'tblsubject.id')
        ->get();
    
        return response()->json(['data' => $curriculums], 200);
    }

    public function viewcuriculum2()
    {
        // Retrieve all records from the manage_curiculum table with related data
        $curriculums = manage_curiculum::select(
           'manage_curiculum.id as curi_id', //id of curiculum
            'tblsubject.id as subject_id',  // Subject ID
            'tblsubject.subjectname',        // Subject name
            'tblstrand.id as strand_id',           // Strand id
            'tblstrand.addstrand',           // Strand name
            'tblstrand.grade_level',         // Grade level
            'manage_curiculum.semester'      // Semester
        )
        ->join('tblsubject', 'manage_curiculum.subject_id', '=', 'tblsubject.id')
        ->join('tblstrand', 'manage_curiculum.strand_id', '=', 'tblstrand.id')
        ->orderBy('tblstrand.addstrand')      // Order by strand name
        ->orderBy('tblstrand.grade_level')     // Order by grade level
        ->orderBy('tblsubject.subjectname')    // Order by subject name
        ->get();
    
        // Group the result by strand (addstrand) and then by grade level
        $groupedData = $curriculums->groupBy('addstrand')->map(function ($strands) {
            return $strands->groupBy('grade_level')->map(function ($levels) {
                return $levels->groupBy('subjectname');
            });
        });
    
        return response()->json(['data' => $groupedData], 200);
    }
    
    public function viewcuriculumforaddclass(Request $request)
    {
        // Validate the request for tblstrand_id and semester (semester as string)
        $validatedData = $request->validate([
            'tblstrand_id' => 'required|integer|exists:tblstrand,id',
            'semester' => 'required|string',  // Semester is a required string
        ]);
    
        // Retrieve the validated tblstrand_id and semester from the request
        $tblstrand_id = $validatedData['tblstrand_id'];
        $semester = $validatedData['semester'];
    
        // Start building the query for managing the curriculum
        $curriculumsQuery = manage_curiculum::select(
            'tblsubject.id as subject_id',  // Subject ID
            'tblsubject.subjectname',        // Subject name
            'tblstrand.addstrand',           // Strand name
            'tblstrand.grade_level',         // Grade level
            'manage_curiculum.semester'      // Semester
        )
        ->join('tblsubject', 'manage_curiculum.subject_id', '=', 'tblsubject.id')
        ->join('tblstrand', 'manage_curiculum.strand_id', '=', 'tblstrand.id')
        ->where('tblstrand.id', $tblstrand_id)  // Filter by tblstrand_id
        ->where('manage_curiculum.semester', $semester) // Filter by semester
        ->orderBy('tblsubject.subjectname')
        ->orderBy('tblstrand.addstrand');
    
    // Execute the query
    $curriculums = $curriculumsQuery->get();

    // Group the result by strand, then by grade_level, then create a list of subjects
    $groupedData = $curriculums->groupBy('addstrand')->map(function ($strands) {
        return $strands->groupBy('grade_level')->map(function ($subjects) {
            return $subjects->map(function ($subject) {
                return [
                    'subject_id' => $subject->subject_id,
                    'subject_name' => $subject->subjectname
                ];
            })->values()->toArray(); // Convert to an array
        });
    });

    // Format the response to match your desired structure
    $formattedData = [];
    foreach ($groupedData as $strand => $grades) {
        $strandData = [
            'strand' => $strand,
            'grades' => [] // Initialize grades as an array
        ]; 
        foreach ($grades as $grade => $subjects) {
            // Create a grade data entry with grade level and subjects
            $strandData['grades'][] = [
                'gradelevel' => $grade,
                'subjects' => $subjects // Assign subjects to the respective grade level
            ];
        }
        $formattedData[] = $strandData; // Add strand data to the formatted response
    }

    return response()->json(['data' => $formattedData], 200);
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


public function viewCurriculum3(Request $request)
{
    $curriculumId = $request->query('curriculumId');
    $strandId = $request->query('strandId');
    $subjectId = $request->query('subjectId');

    if ($curriculumId) {
        if ($strandId) {
            if ($subjectId) {
                // Retrieve specific subject details based on curriculum, strand, and subject IDs
                $subjectDetails = DB::table('tblsubject')
                    ->join('manage_curiculum', 'tblsubject.id', '=', 'manage_curiculum.subject_id')
                    ->where('manage_curiculum.scuriculum_id', $curriculumId)
                    ->where('manage_curiculum.strand_id', $strandId)
                    ->where('tblsubject.id', $subjectId)
                    ->select('tblsubject.id', 'tblsubject.subjectname')
                    ->first();

                if ($subjectDetails) {
                    return response()->json(['subject' => $subjectDetails], 200);
                } else {
                    return response()->json(['error' => 'Subject not found.'], 404);
                }
            } else {
                // Retrieve subjects based on the selected strand and curriculum
                $subjects = DB::table('tblsubject')
                    ->join('manage_curiculum', 'tblsubject.id', '=', 'manage_curiculum.subject_id')
                    ->where('manage_curiculum.scuriculum_id', $curriculumId)
                    ->where('manage_curiculum.strand_id', $strandId)
                    ->select('tblsubject.id', 'tblsubject.subjectname')
                    ->distinct()
                    ->get();

                return response()->json(['subjects' => $subjects], 200);
            }
        } else {
            // Retrieve strands and sections based on the selected curriculum
            $strands = DB::table('tblstrand')
                ->join('manage_curiculum', 'tblstrand.id', '=', 'manage_curiculum.strand_id')
                ->where('manage_curiculum.scuriculum_id', $curriculumId)
                ->select('tblstrand.id', 'tblstrand.addstrand', 'tblstrand.grade_level')
                ->distinct()
                ->get();

            // Retrieve sections for the strands
            $sections = DB::table('tblsection')
                ->join('tblstrand', 'tblsection.strand_id', '=', 'tblstrand.id')
                ->whereIn('tblstrand.id', $strands->pluck('id'))
                ->select('tblsection.id', 'tblsection.section', 'tblsection.strand_id')
                ->distinct()
                ->get();

            return response()->json([
                'strands' => $strands,
                'sections' => $sections
            ], 200);
        }
    } else {
        // Retrieve all curriculums if no parameters are provided
        $curriculums = DB::table('strandcuriculum')
            ->select('id', 'Namecuriculum')
            ->get();

        return response()->json(['curriculums' => $curriculums], 200);
    }

    // If no valid parameters are provided, return an error
    return response()->json(['error' => 'Invalid parameters provided.'], 400);
}

public function updateCurriculum(Request $request, $id)
{
    // Validate the incoming request data, making fields optional for update
    $validator = Validator::make($request->all(), [
        'strand_id' => 'sometimes|exists:tblstrand,id',  // Optional
        'subject_id' => 'sometimes|exists:tblsubject,id', // Optional
        'semester' => 'sometimes|string|max:255',         // Optional
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Find the curriculum entry by its ID
    $curriculum = manage_curiculum::find($id);

    if (!$curriculum) {
        return response()->json(['message' => 'Curriculum not found'], 404);
    }

    // Update the curriculum data only with the fields that are provided
    $curriculum->update($request->only(['strand_id', 'subject_id', 'semester']));

    return response()->json([
        'message' => 'Curriculum updated successfully',
        'data' => $curriculum,
    ], 200);
}


public function deleteCurriculum($id)
{
    // Find the curriculum entry by its ID
    $curriculum = manage_curiculum::find($id);

    if (!$curriculum) {
        return response()->json(['message' => 'Curriculum not found'], 404);
    }

    // Delete the curriculum entry
    $curriculum->delete();

    return response()->json([
        'message' => 'Curriculum deleted successfully',
    ], 200);
}



}
