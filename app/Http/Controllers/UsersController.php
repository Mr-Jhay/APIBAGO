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
use App\Models\tblsubject;
use App\Models\tblsection;
use App\Models\tblposition;
use App\Models\tblteacher;
use App\Models\teacher_strand;
use Illuminate\Support\Facades\Validator;


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
public function getCounts()
{
    try {
        // Check if the authenticated user is an admin
        $user = auth()->user();
        if (!$user || $user->usertype !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Fetch all students along with their strand, section, and sex information
        $students = tblstudent::select(
            'users.id', 
            'users.fname', 
            'users.mname', 
            'users.lname', 
            'users.sex', 
            'tblstudent.Mobile_no', 
            'tblstrand.addstrand as strand_name', 
            'tblstrand.grade_level as grade_level', 
            'tblsection.section as section_name'
        )
        ->join('users', 'tblstudent.user_id', '=', 'users.id') // Join the users table
        ->join('tblstrand', 'tblstudent.strand_id', '=', 'tblstrand.id') // Join the tblstrand table
        ->join('tblsection', 'tblstudent.section_id', '=', 'tblsection.id') // Join the tblsection table
        ->get();

        // Group students by strand and grade level, count males, females, and total students
        $groupedByStrandAndGradeLevel = $students->groupBy(['strand_name', 'grade_level'])->map(function ($groupByGradeLevel) {
            $maleCount = $groupByGradeLevel->where('sex', 'male')->count();
            $femaleCount = $groupByGradeLevel->where('sex', 'female')->count();
            $totalCount = $groupByGradeLevel->count();
            
            return [
                'students' => $groupByGradeLevel,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'total_count' => $totalCount,
            ];
        });

        // Group students by strand, count males, females, and total students
        $groupedByStrand = $students->groupBy('strand_name')->map(function ($groupByStrand) {
            $maleCount = $groupByStrand->where('sex', 'male')->count();
            $femaleCount = $groupByStrand->where('sex', 'female')->count();
            $totalCount = $groupByStrand->count();
            
            return [
                'students' => $groupByStrand,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'total_count' => $totalCount,
            ];
        });

        // Fetch all teachers along with their sex information
        $teachers = tblteacher::select(
            'users.id', 
            'users.fname', 
            'users.mname', 
            'users.lname', 
            'users.sex'
        )
        ->join('users', 'tblteacher.user_id', '=', 'users.id')
        ->get();

        // Group teachers by sex, count males, females, and total teachers
        $groupedBySex = $teachers->groupBy('sex')->map(function ($groupBySex) {
            $maleCount = $groupBySex->where('sex', 'male')->count();
            $femaleCount = $groupBySex->where('sex', 'female')->count();
            $totalCount = $groupBySex->count();
            
            return [
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'total_count' => $totalCount,
            ];
        });

        // Count the number of teachers, students, strands, and subjects
        $teacherCount = tblteacher::count();
        $studentCount = tblstudent::count();
        $strandCount = tblstrand::count();
        $subjectCount = tblsubject::count();

        // Return a success response with the counts and grouped student and teacher data
        return response()->json([
            'status' => true,
            'message' => 'Data retrieved successfully!',
            'data' => [
                'students_grouped_by_strand_and_grade_level' => $groupedByStrandAndGradeLevel,
                'students_grouped_by_strand' => $groupedByStrand,
                'teachers_grouped' => $groupedBySex,
                'counts' => [
                    'teacher_count' => $teacherCount,
                    'student_count' => $studentCount,
                    'strand_count' => $strandCount,
                    'subject_count' => $subjectCount,
                ]
            ]
        ], 200);
    } catch (\Exception $e) {
        // Log the error for debugging
        \Log::error('Failed to retrieve data: ' . $e->getMessage());

        // Return a response with error details
        return response()->json([
            'status' => false,
            'message' => 'Failed to retrieve data',
            'error' => $e->getMessage(),
        ], 500);
    }
}



public function viewAllTeachers()
{
    try {
        // Fetch all teachers along with their user information and associated strands
        // and order by the user's last name (lname) from A to Z
        $teachers = tblteacher::with('user', 'position', 'strands')
                              ->join('users', 'tblteacher.user_id', '=', 'users.id')
                              ->orderBy('users.lname', 'asc')
                              ->select('tblteacher.*','users.id as user_id') // Ensure only tblteacher columns are selected
                              ->get();
        // Count the number of teachers
        $teacherCount = $teachers->count();

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

public function viewAllStudents2()
{
    try {
        // Retrieve all students along with their user information, strand, and section
        // and order by the user's last name (lname) from A to Z
        $students = tblstudent::with(['user', 'strands', 'section'])
                            ->join('users', 'tblstudent.user_id', '=', 'users.id')
                            ->orderBy('users.lname', 'asc')
                            ->select('tblstudent.*', 'users.id as user_id') // Select tblstudent fields and user_id from users
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

public function updateStudentDetails(Request $request, $user_id)
{
    // Validate the incoming request data with all fields optional
    $validated = $request->validate([
        'idnumber' => ['nullable', 'string', 'min:8', 'max:12', 'unique:users,idnumber,' . $user_id],  // Nullable and unique, excluding the current user's idnumber
        'fname' => ['nullable', 'string'],
        'mname' => ['nullable', 'string'],
        'lname' => ['nullable', 'string'],
        'sex' => ['nullable', 'string'],
        'email' => ['nullable', 'email', 'unique:users,email,' . $user_id],  // Nullable and unique, excluding the current user's email
        'password' => [
            'nullable', // Password is optional for updating
            'string',
            'min:8',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
        ],
        'strand_id' => 'nullable|exists:tblstrand,id',
        'section_id' => 'nullable|exists:tblsection,id',
        // 'Mobile_no' => ['nullable', 'string', 'digits:11', 'unique:tblstudent,Mobile_no,' . $user_id],  // Nullable and unique, excluding the current student's mobile number
    ]);

    try {
        // Start a database transaction
        DB::beginTransaction();

        // Find the user by user_id
        $user = User::findOrFail($user_id);

        // Update the user details if provided
        $user->update(array_filter([
            'idnumber' => $validated['idnumber'] ?? $user->idnumber,
            'fname' => $validated['fname'] ?? $user->fname,
            'mname' => $validated['mname'] ?? $user->mname,
            'lname' => $validated['lname'] ?? $user->lname,
            'sex' => $validated['sex'] ?? $user->sex,
            'email' => $validated['email'] ?? $user->email,
            'password' => isset($validated['password']) ? Hash::make($validated['password']) : $user->password,  // Update password only if provided
        ]));

        // Find the student record by user_id
        $student = tblstudent::where('user_id', $user_id)->firstOrFail();

        // Update the student details if provided
        $student->update(array_filter([
            'strand_id' => $validated['strand_id'] ?? $student->strand_id,
            'section_id' => $validated['section_id'] ?? $student->section_id,
            'Mobile_no' => $validated['Mobile_no'] ?? $student->Mobile_no,
        ]));

        // Commit the transaction
        DB::commit();

        // Return a success response with the updated user and student data
        return response()->json([
            'message' => 'Student details updated successfully!',
            'user' => $user,
            'student' => $student,
        ], 200);

    } catch (\Exception $e) {
        // Rollback the transaction if there's an error
        DB::rollBack();

        // Log the error for debugging
        \Log::error('Failed to update student details: ' . $e->getMessage());

        // Return a response with error details
        return response()->json([
            'message' => 'Failed to update student details',
            'error' => $e->getMessage(),
        ], 500);
    }
}




public function updateTeacher(Request $request, $id)
{
    // Validate the incoming request data
    $data = $request->validate([
        'idnumber' => ['sometimes', 'string', 'min:8', 'max:12', 'unique:users,idnumber,' . $id],
        'fname' => ['sometimes', 'string'],
        'mname' => ['sometimes', 'string'],
        'lname' => ['sometimes', 'string'],
        'sex' => ['sometimes', 'string'],
        'email' => ['sometimes', 'email', 'unique:users,email,' . $id],
        // 'password' => [
        //     'sometimes',
        //     'string',
        //     'min:8',
        //     'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
        // ],
        'position_id' => 'sometimes|exists:tblposition,id',
        'strand_id' => 'nullable|array',
        'strand_id.*' => 'exists:tblstrand,id',
    ]);

    try {
        // Start a database transaction
        DB::beginTransaction();

        // Find the user and teacher record by ID
        $user = User::findOrFail($id);
        $teacher = tblteacher::where('user_id', $user->id)->firstOrFail();

        // Update the user record
        $user->update([
            'idnumber' => $data['idnumber'] ?? $user->idnumber,
            'fname' => $data['fname'] ?? $user->fname,
            'mname' => $data['mname'] ?? $user->mname,
            'lname' => $data['lname'] ?? $user->lname,
            'sex' => $data['sex'] ?? $user->sex,
            'email' => $data['email'] ?? $user->email,
            // 'password' => isset($data['password']) ? Hash::make($data['password']) : $user->password,
        ]);

        // Update the teacher record
        $teacher->update([
            'position_id' => $data['position_id'] ?? $teacher->position_id,
        ]);

        // Update strand relationships if provided
        if (isset($data['strand_id'])) {
            // Clear existing strands
            teacher_strand::where('teacher_id', $teacher->id)->delete();

            // Add new strands
            foreach ($data['strand_id'] as $strandId) {
                teacher_strand::create([
                    'teacher_id' => $teacher->id,
                    'strand_id' => $strandId,
                ]);
            }
        }

        // Commit the transaction
        DB::commit();

        // Return a success response
        return response()->json([
            'message' => 'Teacher details updated successfully!',
            'user' => $user,
            'teacher' => $teacher,
        ], 200);

    } catch (\Exception $e) {
        // Rollback the transaction if something goes wrong
        DB::rollBack();

        // Log the error for debugging
        \Log::error('Update failed: ' . $e->getMessage());

        // Return a response with error details
        return response()->json([
            'message' => 'Update failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}



public function updateUserPassword(Request $request, User $user)
{
    // Check if the authenticated user is an admin
    if (auth()->user()->usertype !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Validate the new password with additional rules
    $validator = Validator::make($request->all(), [
        'new_password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
        ],
    ], [
        'new_password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character.',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Update the user's password
    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json(['message' => 'Password updated successfully for user: ' . $user->lname], 200);
}


public function updateOwnPassword(Request $request)
{
    // Validate the new password with additional rules
    $validator = Validator::make($request->all(), [
        'current_password' => 'required',
        'new_password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
        ],
    ], [
        'new_password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character.',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Get the authenticated user
    $user = auth()->user();

    // Check if the current password is correct
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json(['message' => 'Current password is incorrect.'], 401);
    }

    // Update the user's password
    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json(['message' => 'Password updated successfully.'], 200);
}



public function viewallusers()
{
    $users = User::all();
    return response()->json($users);
}

public function getAllStudentsWithStrands()
{
    // Check if the authenticated user is an admin
    $user = auth()->user();
    if (!$user || $user->usertype !== 'admin') {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized access',
        ], 403);
    }

    // Fetch all students along with their strand and section information
    $students = tblstudent::select(
            'tblstudent.id', 
            'tblstudent.fname', 
            'tblstudent.mname', 
            'tblstudent.lname', 
            'tblstudent.Mobile_no', 
            'tblstrand.addstrand as strand_name', 
            'tblstrand.grade_level as grade_level', 
            'tblsection.section as section_name'
        )
        ->join('tblstrand', 'tblstudent.strand_id', '=', 'tblstrand.id')
        ->join('tblsection', 'tblstudent.section_id', '=', 'tblsection.id')
        ->get();

    // Group students by strand
    $groupedByStrand = $students->groupBy('strand_name');

    return response()->json([
        'status' => true,
        'message' => 'Students grouped by strands',
        'data' => $groupedByStrand,
    ], 200);
}


}
