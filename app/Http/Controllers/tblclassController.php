<?php 

namespace App\Http\Controllers;

use App\Models\tblclass;
use App\Models\manage_curiculum;
use Illuminate\Http\Request;

class tblclassController extends Controller
{
    public function addclass(Request $request)
    {
        $validatedData = $request->validate([
            'curriculum' => 'required|exists:manage_curiculum,id',
            'subject_id' => 'required|exists:tblsubject,id',
            'strand_id' => 'required|exists:tblstrand,id',
            'semester' => 'required|string|max:255',
            'section' => 'required|string|max:255',
            'year' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'code' => 'required|string|max:255',
        ]);

        $user = $request->user();

        // Validate that the subject is associated with the selected curriculum, strand, and semester
        $curriculumEntry = manage_curiculum::where('scuriculum_id', $request->curriculum)
            ->where('subject_id', $request->subject_id)
            ->where('strand_id', $request->strand_id)
            ->where('semester', $request->semester)
            ->first();

        if (!$curriculumEntry) {
            return response()->json(['message' => 'Invalid subject selection for the given curriculum, strand, and semester.'], 422);
        }

        if ($user && $user->usertype === 'teacher') {
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
}
