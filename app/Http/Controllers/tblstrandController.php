<?php

namespace App\Http\Controllers;

use App\Models\tblstrand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; 

class tblstrandController extends Controller
{
    public function addstrand(Request $request)
    {
        $validated = $request->validate([
            'addstrand' => 'required|string|max:255',
        ]);

        $strand = tblstrand::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Strand created successfully!',
            'data' => $strand,
        ], 201);
    }

    public function viewstrand()
    {
        $strands = tblstrand::all();

        return response()->json([
            'success' => true,
            'message' => 'Strands retrieved successfully',
            'data' => $strands
        ], 200);
    }

    /**
     * Update the specified strand in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStrand(Request $request, $id)
    {
        $validated = $request->validate([
            'addstrand' => 'required|string|max:255',
        ]);

        $strand = tblstrand::find($id);

        if (!$strand) {
            return response()->json([
                'success' => false,
                'message' => 'Strand not found',
            ], 404);
        }

        $strand->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Strand updated successfully',
            'data' => $strand,
        ], 200);
    }

    /**
     * Remove the specified strand from the database.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteStrand($id)
    {
        $strand = tblstrand::find($id);

        if (!$strand) {
            return response()->json([
                'success' => false,
                'message' => 'Strand not found',
            ], 404);
        }

        $strand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Strand deleted successfully',
        ], 200);
    }
}
