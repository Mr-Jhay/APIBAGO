<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ImagepostRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\tblclass;
use App\Models\manage_curiculum;

use App\Models\strandcuriculum;
use App\Models\tblsubject;
use App\Models\tblstrand;
use App\Models\tblsection;
use App\Models\tblyear;
use Illuminate\Support\Facades\DB;

class ImageComtroller extends Controller
{
    public function store(ImagepostRequest $request)
    {
        try {
            $imageName = Str::random(32).".".$request->image->getClientOriginalExtension();
     
            // Create Post
            Post::create([
                'name' => $request->name,
                'image' => $imageName,
                'description' => $request->description
            ]);
     
            // Save Image in Storage folder
            Storage::disk('public')->put($imageName, file_get_contents($request->image));
     
            // Return Json Response
            return response()->json([
                'message' => "Post successfully created."
            ],200);
        } catch (\Exception $e) {
            // Return Json Response
            return response()->json([
                'message' => "Something went really wrong!"
            ],500);
        }
    }


    public function addclass100(Request $request)
    {

        $imageName = Str::random(32).".".$request->image->getClientOriginalExtension();
        // Validate the request data
        $validatedData = $request->validate([
            'curiculum_id' => 'required|exists:strandcuriculum,id',
            'strand_id' => 'required|exists:tblstrand,id',
            'section_id' => 'required|exists:tblsection,id',
            'subject_id' => 'required|exists:tblsubject,id',
            'year_id' => 'required|exists:tblyear,id',
            'semester' => 'required|string|max:255',
            'class_desc' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            
         //   if ($request->hasFile('image')) {
          //      $imageName = time().'.'.$request->image->extension();
          //      $filePath = $request->image->storeAs('images', $imageName, 'public');
         //       $validatedData['image'] = $filePath;
          //  }

            Storage::disk('public')->put($imageName, file_get_contents($request->image));
    
            // Create the class entry
            $class = tblclass::create(array_merge($validatedData, ['user_id' => $user->id]));
    
            return response()->json(['message' => 'Class created successfully.', 'data' => $class], 201);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }
}
