<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeedbackQuestion; // Model for feedback questions
use App\Models\FeedbackOption; // Model for feedback options
use App\Models\UserFeedback; // Model for user feedback
use App\Models\Comment;
use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exception;


class FeedbackController extends Controller
{
    /**
     * Store a new feedback question.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function storeFeedbackWithOptions(Request $request)
    {
        // Validate request
        $request->validate([
            'class_id' => 'required|exists:tblclass,id',
            'question' => 'required|string',
            'options' => 'required|array',
            'options.*.rating' => 'required|integer|min:1|max:5',
            'options.*.description' => 'nullable|string',
        ]);

        try {
            // Store the feedback question
            $feedbackQuestion = FeedbackQuestion::create([
                'class_id' => $request->class_id,
                'question' => $request->question,
            ]);

            // Store the rating options
            foreach ($request->input('options') as $option) {
                FeedbackOption::create([
                    'tblfeedback_id' => $feedbackQuestion->id,
                    'rating' => $option['rating'],
                    'description' => $option['description'],
                ]);
            }

            return response()->json([
                'message' => 'Feedback question and options created successfully!',
                'feedbackQuestion' => $feedbackQuestion
            ], 201);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Failed to create feedback question and options: ' . $e->getMessage());

            // Return a 500 Internal Server Error
            return response()->json(['error' => 'Failed to create feedback question and options. Please try again later.'], 500);
        }
    }
    public function getFeedbackQuestionsWithOptions($class_id)
    {
        // Validate the class_id
        $request->validate([
            'class_id' => 'required|exists:tblclass,id',
        ]);

        try {
            // Retrieve feedback questions with their options
            $feedbackQuestions = FeedbackQuestion::with('options')
                ->where('class_id', $class_id)
                ->get();

            return response()->json([
                'feedbackQuestions' => $feedbackQuestions
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve feedback questions and options: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve feedback questions and options. Please try again later.'], 500);
        }
    }
    /**
     * Store a new recommendation or suggestion.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFeedback(Request $request)
{
    // Validate the incoming request
    $request->validate([
        'tblfeedback_id' => 'required|exists:feedback_questions,id',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string',
    ]);

    // Store the feedback in the database
    try {
        $feedback = UserFeedback::create([
            'user_id' => Auth::id(),
            'tblfeedback_id' => $request->tblfeedback_id, // Ensure this matches your field name
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Feedback submitted successfully!',
            'feedback' => $feedback
        ], 201);
    } catch (\Exception $e) {
        \Log::error('Failed to submit feedback: ' . $e->getMessage());

        return response()->json([
            'error' => 'Failed to submit feedback. Please try again later.'
        ], 500);
    }
}


    /**
     * Retrieve feedbacks for a specific feedback question.
     *
     * @param int $feedbackQuestionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFeedbacks($feedbackQuestionId)
    {
        $feedbacks = UserFeedback::where('tblfeedback_id', $feedbackQuestionId)->get();

        return response()->json([
            'feedbacks' => $feedbacks
        ]);
    }



    public function commentfeedback(Request $request, $exam_id)
    {
        $user = auth()->user();
    
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:255',
        ]);
    
        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        // Ensure the exam exists in tblschedule table
        $exam = Exam::find($exam_id);
        if (!$exam) {
            return response()->json(['error' => 'Exam not found.'], 404);
        }
    
        try {
            // Create and save the comment
            $comment = new Comment();
            $comment->user_id = $user->id; // Authenticated user's ID
            $comment->exam_id = $exam_id;
            $comment->comment = $request->input('comment');
            $comment->save();
    
            // Return success response
            return response()->json([
                'message' => 'Comment created successfully!',
                'comment' => $comment
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error saving comment: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create comment. Please try again later.'], 500);
        }
    }
    


    public function getComments(Request $request)
{
    try {
        // Validate the request data
        $request->validate([
            'classtable_id' => 'required|integer'
        ]);

        $teacher = Auth::user();

        // Retrieve comments and associated student details for the specified class
        $results = DB::table('tblschedule')
            ->join('joinclass', 'tblschedule.classtable_id', '=', 'joinclass.class_id')
            ->join('users', 'joinclass.user_id', '=', 'users.id')
            ->leftJoin('recomendation_suggestion', function ($join) {
                $join->on('recomendation_suggestion.user_id', '=', 'users.id')
                     ->on('recomendation_suggestion.exam_id', '=', 'tblschedule.id');
            })
            ->select(
                'users.id AS student_id',
                'users.lname AS student_name',
                'users.fname',
                'users.mname',
                'recomendation_suggestion.comment'
            )
            ->where('tblschedule.classtable_id', $request->classtable_id) // Filter by class
            ->where('joinclass.status', 1) // Ensure the student is actively joined
            ->orderBy('users.lname', 'asc') // Sort by student name (lname) alphabetically
            ->get();

        // Group by student
        $groupedResults = $results->groupBy('student_name');

        return response()->json([
            'comments' => $groupedResults
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Error retrieving comments: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve comments. Please try again later.' . $e->getMessage()], 500);
    }
}

}
