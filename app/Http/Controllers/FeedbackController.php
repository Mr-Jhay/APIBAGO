<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeedbackQuestion; // Model for feedback questions
use App\Models\FeedbackOption; // Model for feedback options
use App\Models\UserFeedback; // Model for user feedback
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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



            public function store(Request $request, $exam_id)
        {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'comment' => 'required|string|max:255',
            ]);

            // Return validation errors if any
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            // Ensure the exam_id exists
            if (!tblschedule::where('id', $exam_id)->exists()) {
                return response()->json(['error' => 'Exam not found.'], 404);
            }

            // Create and save the comment
            $comment = new Comment();
            $comment->user_id = Auth::id(); // Use the authenticated user's ID
            $comment->exam_id = $exam_id;
            $comment->comment = $request->input('comment');
            $comment->save();

            // Return success response
            return response()->json(['message' => 'Comment created successfully!', 'comment' => $comment], 201);
        }
}
