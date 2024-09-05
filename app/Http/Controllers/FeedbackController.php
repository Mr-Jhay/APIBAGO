<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeedbackQuestion; // Model for feedback questions
use App\Models\FeedbackOption; // Model for feedback options
use App\Models\UserFeedback; // Model for user feedback
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * Store a new feedback question.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFeedbackQuestion(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:tblclass,id',
            'question' => 'required|string',
        ]);

        $feedbackQuestion = FeedbackQuestion::create([
            'class_id' => $request->class_id,
            'question' => $request->question,
        ]);

        return response()->json([
            'message' => 'Feedback question created successfully!',
            'feedbackQuestion' => $feedbackQuestion
        ], 201);
    }

    /**
     * Store rating options for a feedback question.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFeedbackOptions(Request $request)
    {
        $request->validate([
            'tblfeedback_id' => 'required|exists:feedback_questions,id',
            'rating' => 'required|integer|min:1|max:5',
            'description' => 'nullable|string',
        ]);

        $feedbackOption = FeedbackOption::create([
            'tblfeedback_id' => $request->feedback_question_id,
            'rating' => $request->rating,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Feedback option created successfully!',
            'feedbackOption' => $feedbackOption
        ], 201);
    }

    /**
     * Store a new recommendation or suggestion.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFeedback(Request $request)
    {
        $request->validate([
            'tblfeedback_id' => 'required|exists:feedback_questions,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $feedback = UserFeedback::create([
            'user_id' => Auth::id(),
            'tblfeedback_id' => $request->feedback_question_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Feedback submitted successfully!',
            'feedback' => $feedback
        ], 201);
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
}
