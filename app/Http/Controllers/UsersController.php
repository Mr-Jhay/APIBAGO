<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use App\Http\Resources;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\tblstudent;
use App\Models\tblstrand;
use App\Models\tblsection;
use App\Models\tblposition;
use App\Models\tblteacher;
use App\Models\teacher_strand;

class UsersController extends Controller
{
    public function register(Request $request)
    {
        // Validate the incoming request data
        $data = $request->validate([
            'idnumber' => ['required', 'string', 'min:8', 'max:12', 'unique:users,idnumber'],
            'fname' => ['required', 'string'],
            'mname' => ['required', 'string'],
            'lname' => ['required', 'string'],
            'sex' => ['required', 'string'],
            'usertype' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
           // 'Mobile_no' => ['nullable', 'string', 'digits:11', 'unique:users,Mobile_no'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
            ],
        ]);
    
        try {
            // Create the user
            $user = User::create([
                'idnumber' => $data['idnumber'],
                'fname' => $data['fname'],
                'mname' => $data['mname'],
                'lname' => $data['lname'],
                'sex' => $data['sex'],
                'usertype' => $data['usertype'],
                'email' => $data['email'],
               'Mobile_no' => $data['Mobile_no'] ?? null, // Handle nullable Mobile_no
                'password' => Hash::make($data['password']),
            ]);
    
            // Generate the token
            $token = $user->createToken('auth_token')->plainTextToken;
    
            // Return the user and token
            return response()->json([
                'user' => $user,
                'token' => $token,
            ], 201);
    
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Registration failed: ' . $e->getMessage());
    
            // Return a response with error details
            return response()->json([
                'message' => 'Registration failed',
                'error' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'user' => $user,
            'token' => $token,
            'usertype' => $user->usertype,
        ]);
    }

    public function registerstudent(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'idnumber' => ['required', 'string', 'min:8', 'max:12', 'unique:users,idnumber'],
            'fname' => ['required', 'string'],
            'mname' => ['required', 'string'],
            'lname' => ['required', 'string'],
            'sex' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
            ],
            'strand_id' => 'required|exists:tblstrand,id',
            'section_id' => 'required|exists:tblsection,id',
            'Mobile_no' => ['nullable', 'string', 'digits:11', 'unique:tblstudent,Mobile_no'],
        ]);
    
        try {
            // Start a database transaction
            DB::beginTransaction();
    
            // Automatically set the usertype to 'student'
            $usertype = 'student';
    
            // Create the user
            $user = User::create([
                'idnumber' => $validated['idnumber'],
                'fname' => $validated['fname'],
                'mname' => $validated['mname'],
                'lname' => $validated['lname'],
                'sex' => $validated['sex'],
                'usertype' => $usertype, // Automatically set to 'student'
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
    
            // Create the student record
            $student = tblstudent::create([
                'user_id' => $user->id,
                'strand_id' => $validated['strand_id'],
                'section_id' => $validated['section_id'],
                'Mobile_no' => $validated['Mobile_no'] ?? null,
            ]);
    
            // Commit the transaction
            DB::commit();
    
            // Generate the token
            $token = $user->createToken('auth_token')->plainTextToken;
    
            // Return the user, student record, and token
            return response()->json([
                'user' => $user,
                'student' => $student,
                'token' => $token,
            ], 201);
    
        } catch (\Exception $e) {
            // Rollback the transaction if there's an error
            DB::rollBack();
    
            // Log the error for debugging
            \Log::error('Registration failed: ' . $e->getMessage());
    
            // Return a response with error details
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    

    public function registerTeacher(Request $request)
{
    // Validate the incoming request data
    $data = $request->validate([
        'idnumber' => ['required', 'string', 'min:8', 'max:12', 'unique:users,idnumber'],
        'fname' => ['required', 'string'],
        'mname' => ['required', 'string'],
        'lname' => ['required', 'string'],
        'sex' => ['required', 'string'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => [
            'required',
            'string',
            'min:8',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
        ],
        'position_id' => 'required|exists:tblposition,id',
        'strand_id' => 'nullable',
        'strand_id.*' => 'exists:tblstrand,id',  // Validate array elements if it's an array
    ]);

    try {
        // Start a database transaction
        DB::beginTransaction();

        // Automatically set the usertype to 'teacher'
        $usertype = 'teacher';

        // Create the user
        $user = User::create([
            'idnumber' => $data['idnumber'],
            'fname' => $data['fname'],
            'mname' => $data['mname'],
            'lname' => $data['lname'],
            'sex' => $data['sex'],
            'usertype' => $usertype,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Create the teacher record
        $teacher = tblteacher::create([
            'user_id' => $user->id,
            'position_id' => $data['position_id'],
        ]);

        // Process strand_id if provided
        $strands = $data['strand_id'] ?? [];

        // Ensure $strands is always an array
        if (!is_array($strands)) {
            $strands = [$strands];
        }

        // Create teacher-strand relationships if any strands are provided
        foreach ($strands as $strandId) {
            teacher_strand::create([
                'teacher_id' => $teacher->id,
                'strand_id' => $strandId,
            ]);
        }

        // Generate the token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Commit the transaction
        DB::commit();

        // Return the user, teacher, token, and success message
        return response()->json([
            'message' => 'Registration successful!',
            'user' => $user,
            'teacher' => $teacher,
            'token' => $token,
        ], 201);

    } catch (\Exception $e) {
        // Rollback the transaction
        DB::rollBack();

        // Log the error for debugging
        \Log::error('Registration failed: ' . $e->getMessage());

        // Return a response with error details
        return response()->json([
            'message' => 'Registration failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    
    

public function userprofile(Request $request)
{
    $user = auth()->user();
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not authenticated',
        ], 401);
    }

    // Check for query parameter to determine if only user data should be returned
    $includeUserOnly = $request->query('include', 'false') === 'true';

    $profileData = [
        'id' => $user->id,
        'idnumber' => $user->idnumber,
        'fname' => $user->fname,
        'mname' => $user->mname,
        'lname' => $user->lname,
        'sex' => $user->sex,
        'usertype' => $user->usertype,
        'email' => $user->email,
    ];

    if (!$includeUserOnly) {
        if ($user->usertype === 'student') {
            $student = tblstudent::select('tblstudent.*','tblstrand.addstrand as strand_name','tblstrand.grade_level as grade_level', 'tblsection.section as section_name')
            ->join('tblstrand', 'tblstudent.strand_id', '=', 'tblstrand.id')
            ->join('tblsection', 'tblstudent.section_id', '=', 'tblsection.id')
                
               // ->join('gradelevels', 'tblstudent.gradelevel_id', '=', 'gradelevels.id')
                ->where('tblstudent.user_id', $user->id)
                ->first();

            if ($student) {
                $profileData['student'] = [
                   // 'strand_id' => $student->strand_id,
                    'strand_name' => $student->strand_name,
                    //'section_id' => $student->section_id,
                    'grade_level' => $student->grade_level,
                    'section_name' => $student->section_name,
                  //  'grade_level' => $student->grade_level,
                  // 'gradelevel_id' => $student->gradelevel_id,
                  // 'gradelevel_name' => $student->gradelevel_name,
                    'Mobile_no' => $student->Mobile_no,
                ];
            }
         } elseif ($user->usertype === 'teacher') {
            $teacher = tblteacher::select('tblteacher.*', 'tblposition.teacher_postion as teacher_postion', 'tblstrand.addstrand as addstrand','tblstrand.grade_level as grade_level')
                ->join('tblposition', 'tblteacher.position_id', '=', 'tblposition.id')
                ->join('teacher_strand', 'tblteacher.id', '=', 'teacher_strand.teacher_id')
                ->join('tblstrand', 'teacher_strand.strand_id', '=', 'tblstrand.id')
                ->where('tblteacher.user_id', $user->id)
                ->with('tblstrand')
                ->first();

            if ($teacher) {
                $profileData['teacher'] = [
                   // 'position_id' => $teacher->position_id,
                    'teacher_postion' => $teacher->teacher_postion,
                    'tblstrand' => $teacher->strands->pluck('id')->toArray(),
                    'addstrand' => $teacher->strands->pluck('addstrand','grade_level')->toArray(),
                   // 'addstrand' => $teacher->strands->pluck('addstrand')->toArray(),
                ];
            }
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'User Profile Data',
        'data' => $profileData,
    ], 200);
}


public function logout()
{
    $user = auth()->user();

    if ($user) {
        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout successful',
            'data' => []
        ], 200);
    }

    return response()->json([
        'status' => false,
        'message' => 'Logout failed: User not authenticated',
        'data' => []
    ], 401);
}





public function viewAllTeachers()
{
    try {
        // Fetch all teachers along with their user information and associated strands
        // and order by the user's last name (lname) from A to Z
        $teachers = tblteacher::with('user', 'position', 'strands')
                              ->join('users', 'tblteacher.user_id', '=', 'users.id')
                              ->orderBy('users.lname', 'asc')
                              ->select('tblteacher.*') // Ensure only tblteacher columns are selected
                              ->get();

        // Return a success response with the list of teachers
        return response()->json([
            'message' => 'Teachers retrieved successfully!',
            'teachers' => $teachers,
        ], 200);

    } catch (\Exception $e) {
        // Log the error for debugging
        \Log::error('Failed to retrieve teachers: ' . $e->getMessage());

        // Return a response with error details
        return response()->json([
            'message' => 'Failed to retrieve teachers',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function viewAllStudents()
{
    try {
        // Retrieve all students along with their user information, strand, and section
        // and order by the user's last name (lname) from A to Z
        $students = tblstudent::with(['user', 'strands', 'section'])
                            ->join('users', 'tblstudent.user_id', '=', 'users.id')
                            ->orderBy('users.lname', 'asc')
                            ->select('tblstudent.*') // Ensure you select student fields
                            ->get();

        return response()->json([
            'message' => 'Students retrieved successfully!',
            'students' => $students,
        ], 200);
    } catch (\Exception $e) {
        \Log::error('Failed to retrieve students: ' . $e->getMessage());

        return response()->json([
            'message' => 'Failed to retrieve students',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function updateStudent(Request $request, $id)
{
    // Validate the incoming request data
    $validated = $request->validate([
        'fname' => ['sometimes', 'string'],
        'mname' => ['sometimes', 'string'],
        'lname' => ['sometimes', 'string'],
        'sex' => ['sometimes', 'string'],
        'email' => ['sometimes', 'email', 'unique:users,email,' . $id . ',user_id'],
        'strand_id' => 'sometimes|exists:tblstrand,id',
        'section_id' => 'sometimes|exists:tblsection,id',
        'Mobile_no' => ['nullable', 'string', 'digits:11', 'unique:tblstudent,Mobile_no,' . $id],
    ]);

    try {
        // Start a database transaction
        DB::beginTransaction();

        // Find the student record
        $student = tblstudent::findOrFail($id);

        // Update the user record
        $student->user->update([
            'fname' => $validated['fname'] ?? $student->user->fname,
            'mname' => $validated['mname'] ?? $student->user->mname,
            'lname' => $validated['lname'] ?? $student->user->lname,
            'sex' => $validated['sex'] ?? $student->user->sex,
            'email' => $validated['email'] ?? $student->user->email,
        ]);

        // Update the student record
        $student->update([
            'strand_id' => $validated['strand_id'] ?? $student->strand_id,
            'section_id' => $validated['section_id'] ?? $student->section_id,
            'Mobile_no' => $validated['Mobile_no'] ?? $student->Mobile_no,
        ]);

        // Commit the transaction
        DB::commit();

        return response()->json([
            'message' => 'Student updated successfully!',
            'student' => $student,
        ], 200);
    } catch (\Exception $e) {
        // Rollback the transaction if there's an error
        DB::rollBack();

        \Log::error('Failed to update student: ' . $e->getMessage());

        return response()->json([
            'message' => 'Failed to update student',
            'error' => $e->getMessage(),
        ], 500);
    }
}


public function updateTeacher(Request $request, $id)
{
    // Validate the incoming request data
    $data = $request->validate([
        'fname' => ['sometimes', 'string'],
        'mname' => ['sometimes', 'string'],
        'lname' => ['sometimes', 'string'],
        'sex' => ['sometimes', 'string'],
        'email' => ['sometimes', 'email', 'unique:users,email,' . $id],
        'password' => [
            'sometimes',
            'string',
            'min:8',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
        ],
        'position_id' => 'sometimes|exists:tblposition,id',
        'strand_id' => 'nullable',
        'strand_id.*' => 'exists:tblstrand,id',
    ]);

    try {
        // Start a database transaction
        DB::beginTransaction();

        // Find the teacher and user
        $teacher = tblteacher::with('user')->findOrFail($id);
        $user = $teacher->user;

        // Update user details
        $user->update(array_filter([
            'fname' => $data['fname'] ?? $user->fname,
            'mname' => $data['mname'] ?? $user->mname,
            'lname' => $data['lname'] ?? $user->lname,
            'sex' => $data['sex'] ?? $user->sex,
            'email' => $data['email'] ?? $user->email,
            'password' => isset($data['password']) ? Hash::make($data['password']) : $user->password,
        ]));

        // Update teacher details
        $teacher->update([
            'position_id' => $data['position_id'] ?? $teacher->position_id,
        ]);

        // Update strands
        if (isset($data['strand_id'])) {
            teacher_strand::where('teacher_id', $teacher->id)->delete();
            foreach ($data['strand_id'] as $strandId) {
                teacher_strand::create([
                    'teacher_id' => $teacher->id,
                    'strand_id' => $strandId,
                ]);
            }
        }

        // Commit the transaction
        DB::commit();

        return response()->json([
            'message' => 'Teacher updated successfully!',
            'teacher' => $teacher,
            'user' => $user,
        ], 200);

    } catch (\Exception $e) {
        // Rollback the transaction
        DB::rollBack();
        \Log::error('Updating teacher failed: ' . $e->getMessage());
        return response()->json([
            'message' => 'Updating teacher failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
