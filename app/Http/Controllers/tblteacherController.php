<?php

namespace App\Http\Controllers;

use App\Models\tblteacher;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class tblteacherController extends Controller
{
    public function insertposition(Request $request)//admin sidee add ng position
    {
        // Validate the incoming request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'position_id' => 'required|exists:tblposition,id',
        ]);

        // Create a new teacher record
        $teacher = new tblteacher();
        $teacher->user_id = $request->user_id;
        $teacher->position_id = $request->position_id;
        $teacher->save();

        // Return a response
        return response()->json([
            'message' => 'Teacher record created successfully!',
            'teacher' => $teacher
        ], 201);
    }

    public function insertposition2(Request $request)//teacher side add ng position
{
    // Validate the incoming request data
    $request->validate([
        'position_id' => 'required|exists:tblposition,id',
    ]);

    // Create a new teacher record
    $teacher = new tblteacher();
    $teacher->user_id = auth()->id(); // Get the ID of the authenticated user
    $teacher->position_id = $request->position_id;
    $teacher->save();

    // Return a response
    return response()->json([
        'message' => 'Teacher record created successfully!',
        'teacher' => $teacher
    ], 201);
}

}
