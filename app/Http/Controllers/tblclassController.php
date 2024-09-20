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
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;



class tblclassController extends Controller
{
    public function addclass(Request $request)
{
    // Validate the request data
    $validatedData = $request->validate([
        'strand_id' => 'required|exists:tblstrand,id',
        'section_id' => 'required|exists:tblsection,id',
        'subject_id' => 'required|exists:tblsubject,id',
        'year_id' => 'required|exists:tblyear,id',
        'semester' => 'required|string|max:255',
        'class_desc' => 'nullable|string',
        'profile_img' => 'nullable|file|mimes:jpg,png,jpeg,gif|max:2048', // Image validation
        'gen_code' => 'required|string|max:255',
    ]);

    // Get the authenticated user
    $user = $request->user();

    // Check if the user is authorized (assuming only teachers can add classes)
    if ($user && $user->usertype === 'teacher') {
        // Check if a class with the same details already exists
        $existingClass = tblclass::where([
            'strand_id' => $validatedData['strand_id'],
            'section_id' => $validatedData['section_id'],
            'subject_id' => $validatedData['subject_id'],
            'year_id' => $validatedData['year_id'],
            'semester' => $validatedData['semester'],
        ])->first();

        if ($existingClass) {
            // Return conflict response if the class already exists
            return response()->json(['message' => 'Class with these details already exists.'], 409);
        }

        // Handle file upload if an image is provided
        if ($request->hasFile('profile_img')) {
            // Get the uploaded image file
            $file = $request->file('profile_img');
            // Generate a unique name for the image
            $imageName = time() . '.' . $file->getClientOriginalExtension();
            // Store the image in the public storage folder
            $filePath = $file->storeAs('photos/projects', $imageName, 'public');
            // Save the file path as '/storage/photos/projects/{filename}'
            $validatedData['profile_img'] = '/storage/' . $filePath;
        }

        // Create the class entry with the validated data and user ID
        $class = tblclass::create(array_merge($validatedData, ['user_id' => $user->id]));

        // Return success response along with the created class data (including the image URL)
        return response()->json([
            'message' => 'Class created successfully.',
            'data' => $class  // Send back full class details, including profile_img
        ], 201);

    } else {
        // Return unauthorized response if the user is not a teacher
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}


public function updateaddclass(Request $request, $id)
{
    $class = tblclass::find($id);
    if (!$class) {
        return response()->json(['message' => 'Class not found.'], 404);
    }

    $validatedData = $request->validate([
        'strand_id' => 'nullable|exists:tblstrand,id',
        'section_id' => 'nullable|exists:tblsection,id',
        'subject_id' => 'nullable|exists:tblsubject,id',
        'year_id' => 'nullable|exists:tblyear,id',
        'semester' => 'nullable|string|max:255',
        'class_desc' => 'nullable|string',
        'profile_img' => 'nullable|file|mimes:jpg,png,jpeg,gif|max:2048', // Image validation
        'gen_code' => 'nullable|string|max:255',
    ]);

    $user = $request->user();

    if ($user && $user->usertype === 'teacher') {
        // Handle file upload if an image is provided
        if ($request->hasFile('profile_img')) {
            $file = $request->file('profile_img');
            $imageName = time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('photos/projects', $imageName, 'public');
            $validatedData['profile_img'] = '/storage/' . $filePath;
        }

        // Only update the fields that are provided
        $class->update(array_filter(array_merge($validatedData, ['user_id' => $user->id])));

        return response()->json(['message' => 'Class updated successfully.', 'data' => $class], 200);
    } else {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
    

 
    public function viewAllClassDetails(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Check if the user is authorized and is a teacher
        if ($user && $user->usertype === 'teacher') {
            // Retrieve all classes created by this teacher with related data
            $classes = tblclass::with(['strand', 'section', 'subject', 'year'])  // Adjust the relations based on your models
                            ->where('user_id', $user->id)
                            ->get();

            if ($classes->isNotEmpty()) {
                return response()->json(['classes' => $classes], 200);
            } else {
                return response()->json(['message' => 'No classes found.'], 404);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }



    public function showClass(Request $request, $class_id)
    {
        // Get the authenticated user
        $user = $request->user();
    
        // Check if the user is authorized and is a teacher
        if ($user && $user->usertype === 'teacher') {
            // Retrieve the class created by this teacher with related data
            $class = tblclass::with(['strand', 'section', 'subject', 'year']) // Adjust relations as necessary
                            ->where('id', $class_id)
                            ->where('user_id', $user->id) // Ensure class belongs to the teacher
                            ->first();
    
            if ($class) {
                return response()->json(['class' => $class], 200);
            } else {
                return response()->json(['message' => 'Class not found or you are not authorized to view this class.'], 404);
            }
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


    public function getStudentClassrooms()
    {
        // Retrieve the authenticated user
        $user = auth()->user();

        // Ensure the user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can view their classroom list.'
            ], 403); // HTTP Forbidden
        }

        // Fetch the classes the student has joined
        $classrooms = \DB::table('joinclass')
                        ->join('tblclass', 'joinclass.class_id', '=', 'tblclass.id')
                        ->where('joinclass.user_id', $user->id)
                        ->select('tblclass.id', 'tblclass.subject_id', 'tblclass.description', 'tblclass.gen_code', 'joinclass.status')
                        ->get();

        // Return the list of classrooms with a 200 status code
        return response()->json($classrooms, 200); // HTTP OK
    }


    public function getStudentClassroomDetails()
{
    // Retrieve the authenticated user
    $user = auth()->user();

    // Ensure the user is a student
    if ($user->usertype !== 'student') {
        return response()->json([
            'error' => 'Unauthorized: Only students can view their classroom details.'
        ], 403); // HTTP Forbidden
    }

    // Fetch the classes the student has joined where the status is approved (1)
    $classrooms = \DB::table('joinclass')
                    ->join('tblclass', 'joinclass.class_id', '=', 'tblclass.id')
                    ->leftJoin('tblsubject', 'tblclass.subject_id', '=', 'tblsubject.id') // Assuming tblclass has a foreign key to tblsubject
                    ->where('joinclass.user_id', $user->id)
                    ->where('joinclass.status', 1) // Ensure the status is approved
                    ->select(
                        'tblclass.id as class_id',
                        'tblclass.class_desc as class_description',
                        'tblclass.gen_code as class_gen_code',
                        'tblsubject.subjectname as subject_name' // Assuming tblsubject has a 'subjectname' field
                    )
                    ->get();

    // Return the detailed list of classrooms with a 200 status code
    return response()->json($classrooms, 200); // HTTP OK
}



    public function getStudentClassroomDetails2(Request $request)
    {
        // Retrieve the authenticated user
        $user = auth()->user();
    
        // Ensure the user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can view their classroom details.'
            ], 403); // HTTP Forbidden
        }
    
        // Validate the incoming request to ensure class_id is provided
        $request->validate([
            'class_id' => 'required|exists:tblclass,id'
        ]);
    
        // Fetch the specific class the student has joined where the status is approved (1)
        $classroom = \DB::table('joinclass')
                        ->join('tblclass', 'joinclass.class_id', '=', 'tblclass.id')
                        ->leftJoin('tblsubject', 'tblclass.subject_id', '=', 'tblsubject.id') // Assuming tblclass has a foreign key to tblsubject
                        ->where('joinclass.user_id', $user->id)
                        ->where('joinclass.status', 1) // Ensure the status is approved
                        ->where('tblclass.id', $request->class_id) // Filter by class_id
                        ->select(
                            'tblsubject.id as subject_id',
                            'tblsubject.subjectname as subject_name',
                            'tblclass.id as class_id',
                            'tblclass.name as class_name',
                            'tblclass.description as class_description',
                            'tblclass.gen_code as class_gen_code',
                            'tblclass.semester',  // Ensure semester is included
                            'tblclass.school_year',  // Ensure school year is included
                            'joinclass.status as join_status'
                        )
                        ->first(); // Get a single record
    
        // Check if the class is found
        if (!$classroom) {
            return response()->json([
                'error' => 'Classroom not found or you do not have access to this classroom.'
            ], 404); // HTTP Not Found
        }
    
        // Return the classroom details
        return response()->json($classroom, 200); // HTTP OK
    }
    

public function getSingleClassroomDetails($class_id)
{
    // Retrieve the authenticated user
    $user = auth()->user();

    // Ensure the user is a student
    if ($user->usertype !== 'student') {
        return response()->json([
            'error' => 'Unauthorized: Only students can view their classroom details.'
        ], 403); // HTTP Forbidden
    }

    // Fetch the specific class the student has joined where the status is approved (1)
    $classroom = \DB::table('joinclass')
                    ->join('tblclass', 'joinclass.class_id', '=', 'tblclass.id')
                    ->leftJoin('tblsubject', 'tblclass.subject_id', '=', 'tblsubject.id')
                   // ->leftJoin('semester', 'tblclass.subject_id', '=', 'semester.id')
                    ->leftJoin('tblyear', 'tblclass.year_id', '=', 'tblyear.id') // Assuming tblclass has a foreign key to tblsubject
                    ->where('joinclass.user_id', $user->id)
                    ->where('joinclass.class_id', $class_id) // Ensure it matches the specific class ID
                    ->where('joinclass.status', 1) // Ensure the status is approved
                    ->select(
                        'tblclass.id as class_id',
                        'tblclass.class_desc as class_description',
                        'tblclass.gen_code as class_gen_code',
                        'tblclass.semester as class_semester',
                        'tblyear.addyear as class_addyear',
                        'tblsubject.subjectname as subject_name' // Assuming tblsubject has a 'subjectname' field
                    )
                    ->first(); // Fetch only one record

    // If the class is not found or the user is not enrolled, return an error
    if (!$classroom) {
        return response()->json([
            'error' => 'Unauthorized: You are not enrolled in this class or the class does not exist.'
        ], 403); // HTTP Forbidden
    }

    // Return the detailed information about the classroom with a 200 status code
    return response()->json($classroom, 200); // HTTP OK
}



    public function showClass2(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Check if the user is authorized and is a teacher
        if ($user && $user->usertype === 'teacher') {
            // Automatically determine the class ID
            $class_id = $this->getClassIdForTeacher($user->id);

            // Retrieve the class created by this teacher with related data
            $class = tblclass::with(['strand', 'section', 'subject', 'year']) // Adjust relations as necessary
                            ->where('id', $class_id)
                            ->where('user_id', $user->id)
                            ->first();

            if ($class) {
                return response()->json(['class' => $class], 200);
            } else {
                // If the class is not found or doesn't belong to the teacher
                return response()->json(['message' => 'Class not found or you are not authorized to view this class.'], 404);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }


    private function getClassIdForTeacher($teacherId)
    {
        // Fetch the most recently created class for the teacher
        $class = tblclass::where('user_id', $teacherId)
                        ->orderBy('created_at', 'desc') // Order by creation date
                        ->first(); // Get the first result (most recent)

        return $class->id ?? null; // Return the class ID or null if no class is found
    }


}
