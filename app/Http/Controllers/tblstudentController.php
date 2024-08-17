<?php

namespace App\Http\Controllers;

use App\Models\tblstudent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class tblstudentController extends Controller
{
    public function addinfostudent(Request $request)//pag add ng information ng student sa admin
    {
        // Validate the incoming request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:tblsection,id',
            'strand_id' => 'required|exists:tblstrand,id',
            'gradelevel_id' => 'required|exists:gradelevel,id',
            'Mobile_no' => 'required|string|max:15',
        ]);

        // Create a new student record
        $student = new tblstudent();
        $student->user_id = $request->user_id;
        $student->section_id = $request->section_id;
        $student->strand_id = $request->strand_id;
        $student->gradelevel_id = $request->gradelevel_id;
        $student->Mobile_no = $request->Mobile_no;
        $student->save();

        // Return a response
        return response()->json([
            'message' => 'Student record created successfully!',
            'student' => $student
        ], 201);
    }

    public function infoStudent(Request $request)//pag add ng info pag student
{
    // Validate the incoming request data
    $request->validate([
        'section_id' => 'required|exists:tblsection,id',
        'strand_id' => 'required|exists:tblstrand,id',
        'gradelevel_id' => 'required|exists:gradelevel,id',
        'Mobile_no' => 'required|string|max:15',
    ]);

    // Create a new student record
    $student = new tblstudent();
    $student->user_id = auth()->id(); // Get the ID of the authenticated user
    $student->section_id = $request->section_id;
    $student->strand_id = $request->strand_id;
    $student->gradelevel_id = $request->gradelevel_id;
    $student->Mobile_no = $request->Mobile_no;
    $student->save();

    // Return a response
    return response()->json([
        'message' => 'Student record created successfully!',
        'student' => $student
    ], 201);
}

}
