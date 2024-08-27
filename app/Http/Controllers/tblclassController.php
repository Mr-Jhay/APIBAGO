<?php 

namespace App\Http\Controllers;

use App\Models\tblclass;
use App\Models\manage_curiculum;
use Illuminate\Http\Request;
use App\Models\strandcuriculum;
use App\Models\tblsubject;
use App\Models\tblstrand;
use App\Models\tblsection;
use App\Models\tblyear;


class tblclassController extends Controller
{
    public function addclass(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'curriculum' => 'required|exists:manage_curiculum,id',
            'strand_id' => 'required|exists:tblstrand,id',
            'section_id' => 'required|exists:tblsection,id',
            'subject_id' => 'required|exists:tblsubject,id',
            'year_id' => 'required|exists:tblyear,id',
            'semester' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'gen_code' => 'required|string|max:255',
        ]);
    
        // Get the authenticated user
        $user = $request->user();
    
        // Validate that the subject is associated with the selected curriculum, strand, and semester
        $curriculumEntry = manage_curiculum::where('id', $request->curriculum)
             ->where('strand_id', $request->strand_id)
            ->where('subject_id', $request->subject_id)
            ->where('semester', $request->semester)
            ->first();
    
        if (!$curriculumEntry) {
            return response()->json(['message' => 'Invalid subject selection for the given curriculum, strand, and semester.'], 422);
        }
    
        // Check if the user is authorized
        if ($user && $user->usertype === 'teacher') {
            // Create the class entry
            $class = tblclass::create(array_merge($validatedData, ['user_id' => $user->id]));
            return response()->json(['message' => 'Class created successfully.', 'data' => $class], 201);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }
    

    public function getCurriculums()
    {
        $curriculums = manage_curiculum::with('subjects')->get(); // Eager load subjects
        return response()->json($curriculums);
    }





    public function getCurriculum($id)
    {
        $curriculum = manage_curriculum::find($id);
        if (!$curriculum) {
            return response()->json(['error' => 'Curriculum not found'], 404);
        }
    
        return $curriculum;
    }
    public function getStrandIdAndValue($scuriculumId)
{
    $curriculum = manage_curriculum::where('scuriculum_id', $scuriculumId)->first();
    if (!$curriculum) {
        return response()->json(['error' => 'Curriculum not found'], 404);
    }

    $strandId = $curriculum->strand_id;
    $strand = tblstrand::find($strandId); // Assuming you have a Strand model

    if (!$strand) {
        return response()->json(['error' => 'Strand not found'], 404);
    }

    return [
        'strand_id' => $strandId,
        'strand_value' => $strand->value // Assuming 'value' is a column in the Strand table
    ];
}
public function getAvailableSubjects($scuriculumId, $strandId)
{
    $subjects = tblsubject::where('strand_id', $strandId)
                       ->where('curriculum_id', $scuriculumId)
                       ->get();

    return $subjects;
}

public function getCurriculumDetails($id)
{
    // Retrieve the Curriculum
    $curriculum = manage_curriculum::find($id);
    if (!$curriculum) {
        return response()->json(['error' => 'Curriculum not found'], 404);
    }

    $scuriculumId = $curriculum->scuriculum_id;

    // Retrieve Strand ID and its details
    $strandId = $curriculum->strand_id;
    $strand = tblstrand::find($strandId); // Assuming you have a Strand model
    if (!$strand) {
        return response()->json(['error' => 'Strand not found'], 404);
    }

    // Retrieve Available Subjects
    $subjects = tblsubject::where('strand_id', $strandId)
                       ->where('curriculum_id', $scuriculumId)
                       ->get();

    return response()->json([
        'curriculum_id' => $scuriculumId,
        'namecuriculum' => $curriculum->namecuriculum, // Assuming 'namecuriculum' is a column in ManageCurriculum
        'strand_id' => $strandId,
        'strand' => $strand->strand, // Assuming 'strand' is a column in Strand
        'grade_level' => $strand->grade_level, // Assuming 'grade_level' is a column in Strand
        'subjects' => $subjects
    ]);
}

}
