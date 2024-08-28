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
use Illuminate\Support\Facades\DB;



class tblclassController extends Controller
{
    public function addclass(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'curiculum_id' => 'required|exists:strandcuriculum,id',
            'strand_id' => 'required|exists:tblstrand,id',
            'section_id' => 'required|exists:tblsection,id',
            'subject_id' => 'required|exists:tblsubject,id',
            'year_id' => 'required|exists:tblyear,id',
            'semester' => 'required|string|max:255',
            'class_desc' => 'nullable|string',
            'image' => 'nullable|string',
            'gen_code' => 'required|string|max:255',
        ]);
    
        // Get the authenticated user
        $user = $request->user();
    
        // Check if the user is authorized
        if ($user && $user->usertype === 'teacher') {
            // Check if a class with the same details already exists
            $existingClass = tblclass::where([
                'curiculum_id' => $validatedData['curiculum_id'],
                'strand_id' => $validatedData['strand_id'],
                'section_id' => $validatedData['section_id'],
                'subject_id' => $validatedData['subject_id'],
                'year_id' => $validatedData['year_id'],
                'semester' => $validatedData['semester'],
            ])->first();
    
            if ($existingClass) {
                // If a class with the same details exists, return a conflict response
                return response()->json(['message' => 'Class with these details already exists.'], 409);
            }
    
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
    $curriculum = manage_curiculum::find($id);
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

     public function getCurriculumDetails2(Request $request)
    {
        $curriculum = manage_curiculum::join('tblstrand', 'manage_curiculum.strand_id', '=', 'tblstrand.id')
            ->join('tblsubject', 'manage_curiculum.subject_id', '=', 'tblsubject.id')
            ->join('tblyear', 'manage_curiculum.year_id', '=', 'tblyear.id')
            ->where('manage_curiculum.id', $request->Namecuriculum)
            ->where('manage_curiculum.strand_id', $request->strand_id)
            ->where('manage_curiculum.subject_id', $request->subject_id)
            ->where('manage_curiculum.year_id', $request->year_id)
            ->where('manage_curiculum.semester', $request->semester)
            ->where('tblstrand.gradelevel', $request->gradelevel)
            ->select('manage_curiculum.*')
            ->first();

        if ($curriculum) {
            return response()->json($curriculum);
        } else {
            return response()->json(['message' => 'Curriculum not found'], 404);
        }
    }


    public function getAllCurriculums()
{
    $curriculums = manage_curiculum::all();
    return response()->json($curriculums);
}
public function getStrandsByCurriculum($curriculumId)
{
    $strands = tblstrand::where('curriculum_id', $curriculumId)->get();
    return response()->json($strands);
}


public function getAllCurriculums9()
{
    $curriculums = DB::table('manage_curiculum')
        ->join('strandcuriculum', 'manage_curiculum.scuriculum_id', '=', 'strandcuriculum.id')
        ->select('manage_curiculum.scuriculum_id', 'strandcuriculum.Namecuriculum')
        ->get();
        
    return response()->json($curriculums);
}
public function getAllStrandDetailsByCurriculum()
{
    $strandDetails = DB::table('manage_curiculum')
        ->join('tblstrand', 'manage_curiculum.strand_id', '=', 'tblstrand.id')
       // ->where('manage_curiculum.strand_id', $scuriculumId)
        ->select('tblstrand.addstrand', 'tblstrand.grade_level') // Adjust column names as needed
        ->get();
        
    return response()->json($strandDetails);
}
public function getCurriculumsWithSameStrand($scuriculum_id)
{
    $strand_id = manage_curiculum::where('scuriculum_id', $scuriculum_id)->value('strand_id');

    if ($strand_id) {
        $curriculums = manage_curiculum::where('strand_id', $strand_id)->get();
        return response()->json($curriculums);
    } else {
        return response()->json(['message' => 'Strand not found'], 404);
    }




    
}

public function getAllStrandDetailsByCurriculum1($scuriculumId)
{
    $strandDetails = DB::table('manage_curiculum')
        ->join('strandcuriculum', 'manage_curiculum.scuriculum_id', '=', 'strandcuriculum.id')
        ->join('tblstrand', 'manage_curiculum.strand_id', '=', 'tblstrand.id')
        ->where('manage_curiculum.strand_id', $scuriculumId)
        ->select('strandcuriculum.Namecuriculum', 'tblstrand.addstrand', 'tblstrand.grade_level')
        ->get();

    // If you need to group the results by curriculum name
    $curriculum = $strandDetails->first(); // Get the first result to access curriculum name
    $strands = $strandDetails->map(function($item) {
        return [
            'addstrand' => $item->addstrand,
            'grade_level' => $item->grade_level
        ];
    });

    return response()->json([
        'Namecuriculum' => $curriculum,
        'strands' => $strands
    ]);
}




}
