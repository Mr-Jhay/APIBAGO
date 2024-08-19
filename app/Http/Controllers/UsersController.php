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
               // 'Mobile_no' => $data['Mobile_no'] ?? null, // Handle nullable Mobile_no
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
            // Create the user
            $user = User::create([
                'idnumber' => $validated['idnumber'],
                'fname' => $validated['fname'],
                'mname' => $validated['mname'],
                'lname' => $validated['lname'],
                'sex' => $validated['sex'],
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
    
            // Generate the token
            $token = $user->createToken('auth_token')->plainTextToken;
    
            // Return the user, student record, and token
            return response()->json([
                'user' => $user,
                'student' => $student,
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
    

public function registerTeacher(Request $request)
{
    // Validate the incoming request data
    $data = $request->validate([
        'idnumber' => ['required', 'string', 'min:8', 'max:12', 'unique:users,idnumber'],
        'fname' => ['required', 'string'],
        'mname' => ['required', 'string'],
        'lname' => ['required', 'string'],
        'sex' => ['required', 'string'],
        'usertype' => ['required', 'string', 'in:teacher'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => [
            'required',
            'string',
            'min:8',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
        ],
        'position_id' => ['required', 'exists:tblposition,id'],
        'strand_ids' => ['required', 'array'],
        'strand_ids.*' => ['exists:tblstrand,id'],
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
            'password' => Hash::make($data['password']),
        ]);

        // Create the teacher record
        $teacher = new tblteacher();
        $teacher->user_id = $user->id;
        $teacher->position_id = $data['position_id'];
        $teacher->save();

        // Attach strands to the teacher
        $teacher->strands()->sync($data['strand_ids']);

        // Generate the token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return the user, teacher, strands, and token
        return response()->json([
            'user' => $user,
            'teacher' => $teacher->load('strands'),
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
        // Load additional data based on usertype if available
        if ($user->usertype === 'student') {
            $student = tblstudent::where('user_id', $user->id)->first();
            if ($student) {
                $profileData['student'] = [
                    'section_id' => $student->section_id,
                    'strand_id' => $student->strand_id,
                    'gradelevel_id' => $student->gradelevel_id,
                    'Mobile_no' => $student->Mobile_no,
                ];
            }
        } elseif ($user->usertype === 'teacher') {
            $teacher = tblteacher::where('user_id', $user->id)->with('strands')->first();
            if ($teacher) {
                $profileData['teacher'] = [
                    'position_id' => $teacher->position_id,
                    'strands' => $teacher->strands->pluck('id')->toArray(),
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


}
