<?php

namespace App\Http\Controllers;

use App\Models\teacher_strand;
use App\Models\tblteacher;
use App\Models\tblstrand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class teacher_strandController extends Controller
{
    public function addstrandteacher(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'teacher_id' => 'required|exists:tblteacher,id',
            'strand_ids' => 'required|array',
            'strand_ids.*' => 'exists:tblstrand,id',
        ]);

        // Find the teacher by ID
        $teacher = tblteacher::findOrFail($request->teacher_id);

        // Attach strands to the teacher
        $teacher->strands()->sync($request->strand_ids);

        // Return a response
        return response()->json([
            'message' => 'Strands assigned to the teacher successfully!',
            'teacher' => $teacher->load('strands') // Load strands relationship
        ], 201);
    }

    public function addstrandteacher2(Request $request)
{
    // Validate the incoming request data
    $request->validate([
        'strand_ids' => 'required|array',
        'strand_ids.*' => 'exists:tblstrand,id',
    ]);

    // Get the authenticated user's teacher record
    $teacher = tblteacher::where('user_id', auth()->id())->firstOrFail();

    // Attach strands to the teacher
    $teacher->strands()->sync($request->strand_ids);

    // Return a response
    return response()->json([
        'message' => 'Strands assigned to the teacher successfully!',
        'teacher' => $teacher->load('strands')
    ], 201);
}

}
