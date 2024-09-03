<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use App\Http\Resources;
use App\Http\Resources\UserResource;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Choice;
use App\Models\CorrectAnswer;
use App\Models\tblclass;

class ExamController extends Controller
{
    public function addExam(Request $request)
    {
        $request->validate([
            'classtable_id' => 'required|exists:tblclass,id',
            'title' => 'required|string',
            'quarter' => 'required|string',
            'start' => 'required|date_format:Y-m-d H:i:s',
            'end' => 'required|date_format:Y-m-d H:i:s',
            'questions' => 'required|array',
            'questions.*.question_type' => 'required|string',
            'questions.*.question' => 'required|string',
            'questions.*.choices' => 'nullable|array',
            'questions.*.correct_answers' => 'nullable|array',
            'questions.*.correct_answers.*.choice_id' => 'nullable|exists:addchoices,id',
            'questions.*.correct_answers.*.correct_answer' => 'nullable|string',
            'questions.*.correct_answers.*.points' => 'nullable|integer'
        ]);

        // Create exam
        $exam = Exam::create($request->only(['classtable_id', 'title', 'quarter', 'start', 'end']));

        foreach ($request->input('questions') as $qData) {
            $question = Question::create([
                'tblschedule_id' => $exam->id,
                'question_type' => $qData['question_type'],
                'question' => $qData['question']
            ]);

            if (isset($qData['choices'])) {
                foreach ($qData['choices'] as $choice) {
                    Choice::create([
                        'tblquestion_id' => $question->id,
                        'choices' => $choice
                    ]);
                }
            }

            if (isset($qData['correct_answers'])) {
                foreach ($qData['correct_answers'] as $ansData) {
                    CorrectAnswer::create([
                        'tblquestion_id' => $question->id,
                        'addchoices_id' => $ansData['choice_id'] ?? null,
                        'correct_answer' => $ansData['correct_answer'] ?? null,
                        'points' => $ansData['points'] ?? 0
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Exam created successfully',
            'exam' => $exam
        ], 201); // HTTP Created
    }

    
    public function viewExamForTeacher($exam_id)
    {
        try {
            // Ensure relationships are properly defined in your models
            $exam = Exam::with(['questions.choices', 'class'])->findOrFail($exam_id);
    
            return response()->json([
                'exam' => $exam,
            ], 200); // HTTP OK
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            Log::error('Error fetching exam details: ' . $e->getMessage());
    
            // Return a more user-friendly error message
            return response()->json(['error' => 'Failed to retrieve exam details. Please try again later.'], 500);
        }
    }
    
    
    public function viewExam($exam_id)//View Exam for Students
    {
        $user = auth()->user();

        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can view exams.'
            ], 403); // HTTP Forbidden
        }

        // Check if the student is enrolled in the exam
        $isEnrolled = StudentExam::where('user_id', $user->id)
                                 ->where('tblschedule_id', $exam_id)
                                 ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'error' => 'Unauthorized: You are not enrolled in this exam.'
            ], 403); // HTTP Forbidden
        }

        $exam = Exam::with(['questions.choices', 'questions.correctAnswers'])
                    ->find($exam_id);

        if (!$exam) {
            return response()->json([
                'error' => 'Exam not found.'
            ], 404); // HTTP Not Found
        }

        return response()->json($exam, 200); // HTTP OK
    }


    public function viewExam2($exam_id)//View Exam for Students with logic
    {
        $user = auth()->user();

        // Ensure only students can view exams
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can view exams.'
            ], 403); // HTTP Forbidden
        }

        // Check if the student is enrolled in the exam
        $isEnrolled = StudentExam::where('user_id', $user->id)
                                 ->where('tblschedule_id', $exam_id)
                                 ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'error' => 'Unauthorized: You are not enrolled in this exam.'
            ], 403); // HTTP Forbidden
        }

        // Fetch exam details
        $exam = Exam::with([
            'questions' => function ($query) {
                $query->with(['choices', 'correctAnswers']);
            }
        ])->find($exam_id);

        if (!$exam) {
            return response()->json([
                'error' => 'Exam not found.'
            ], 404); // HTTP Not Found
        }

        // Prepare exam data with additional details
        $examData = [
            'id' => $exam->id,
            'title' => $exam->title,
            'quarter' => $exam->quarter,
            'start' => $exam->start,
            'end' => $exam->end,
            'questions' => $exam->questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question' => $question->question,
                    'question_type' => $question->question_type,
                    'choices' => $question->choices->map(function ($choice) {
                        return [
                            'id' => $choice->id,
                            'choice' => $choice->choices
                        ];
                    }),
                    'correct_answers' => $question->correctAnswers->map(function ($correctAnswer) {
                        return [
                            'choice_id' => $correctAnswer->addchoices_id,
                            'correct_answer' => $correctAnswer->correct_answer,
                            'points' => $correctAnswer->points
                        ];
                    })
                ];
            })
        ];

        return response()->json($examData, 200); // HTTP OK
    }

    public function submitExam8(Request $request, $exam_id)//pag submit ng exam sa student
    {
        $user = auth()->user();

        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can submit exams.'], 403);
        }

        $isEnrolled = StudentExam::where('user_id', $user->id)
                                 ->where('tblschedule_id', $exam_id)
                                 ->exists();

        if (!$isEnrolled) {
            return response()->json(['error' => 'Unauthorized: You are not enrolled in this exam.'], 403);
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'required|integer|exists:addchoices,id' // Validate each answer ID
        ]);

        foreach ($request->input('answers') as $question_id => $choice_id) {
            $correctAnswer = CorrectAnswer::where('tblquestion_id', $question_id)
                                          ->where('addchoices_id', $choice_id)
                                          ->first();

            $answeredQuestion = new AnsweredQuestion([
                'users_id' => $user->id,
                'tblquestion_id' => $question_id,
                'correctanswer_id' => $correctAnswer ? $correctAnswer->id : null
            ]);

            $answeredQuestion->save();
        }

        return response()->json(['message' => 'Exam submitted successfully.'], 200);
    }

    public function getExam($id)
    {
        $exam = Exam::with(['questions.choices', 'questions.correctAnswer'])
            ->find($id);

        if (!$exam) {
            return response()->json(['error' => 'Exam not found'], 404);
        }

        return response()->json($exam, 200);
    }

    // Update an existing exam
    public function updateExam(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'quarter' => 'required|string|max:50',
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['error' => 'Exam not found'], 404);
        }

        $exam->update([
            'title' => $request->title,
            'quarter' => $request->quarter,
            'start' => $request->start,
            'end' => $request->end
        ]);

        return response()->json(['message' => 'Exam updated successfully'], 200);
    }

    // Delete an exam
    public function deleteExam($id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json(['error' => 'Exam not found'], 404);
        }

        // Optional: Handle related records (questions, choices) deletion if needed
        $exam->delete();

        return response()->json(['message' => 'Exam deleted successfully'], 200);
    }

    //strat of exam

    public function startExam(Request $request, $examId)
    {
        // Validate request
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();

        // Ensure the user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can start exams.'
            ], 403); // HTTP Forbidden
        }

        // Check if the student has already started the exam
        $existingExam = StudentExam::where('user_id', $user->id)
            ->where('tblschedule_id', $examId)
            ->first();

        if ($existingExam) {
            return response()->json([
                'message' => 'Exam already started.'
            ], 200);
        }

        // Register the student's attempt
        $studentExam = StudentExam::create([
            'user_id' => $user->id,
            'tblschedule_id' => $examId,
        ]);

        return response()->json([
            'message' => 'Exam started successfully.',
            'student_exam_id' => $studentExam->id
        ], 201);
    }

    public function submitAnswers(Request $request)
    {
        // Validate request
        $request->validate([
            'student_exam_id' => 'required|exists:studentexam,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:tblquestion,id',
            'answers.*.correctanswer_id' => 'required|exists:addchoices,id',
        ]);

        $user = auth()->user();

        // Ensure the user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can submit answers.'
            ], 403); // HTTP Forbidden
        }

        // Validate that the student is attempting an exam
        $studentExam = StudentExam::find($request->student_exam_id);

        if (!$studentExam || $studentExam->user_id !== $user->id) {
            return response()->json([
                'error' => 'Invalid exam attempt.'
            ], 400); // HTTP Bad Request
        }

        // Save answers
        foreach ($request->answers as $answer) {
            AnsweredQuestion::create([
                'users_id' => $user->id,
                'tblquestion_id' => $answer['question_id'],
                'correctanswer_id' => $answer['correctanswer_id']
            ]);
        }

        return response()->json([
            'message' => 'Answers submitted successfully.'
        ], 200);
    }

    public function getResults(Request $request, $examId)
    {
        $user = auth()->user();

        // Ensure the user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can view results.'
            ], 403); // HTTP Forbidden
        }

        // Retrieve the student's answers
        $results = AnsweredQuestion::where('users_id', $user->id)
            ->whereHas('question', function ($query) use ($examId) {
                $query->where('tblschedule_id', $examId);
            })
            ->with('question', 'correctAnswer')
            ->get();

        // Calculate the score
        $score = $results->filter(function ($result) {
            return $result->correctAnswer->addchoices_id === $result->correctanswer_id;
        })->count();

        return response()->json([
            'results' => $results,
            'score' => $score
        ], 200);
    }

    public function getStudentExams()
    {
        // Retrieve the authenticated user
        $user = auth()->user();

        // Ensure the user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can view their exams.'
            ], 403); // HTTP Forbidden
        }

        // Fetch the exams that the student is enrolled in
        $exams = StudentExam::with('exam')
                            ->where('user_id', $user->id)
                            ->get()
                            ->map(function ($studentExam) {
                                return [
                                    'exam_id' => $studentExam->exam->id,
                                    'title' => $studentExam->exam->title,
                                    'quarter' => $studentExam->exam->quarter,
                                    'start' => $studentExam->exam->start,
                                    'end' => $studentExam->exam->end
                                ];
                            });

        // Return the list of exams with a 200 status code
        return response()->json($exams, 200); // HTTP OK
    }

        public function getExamDetails($exam_id)
    {
        // Retrieve the authenticated user
        $user = auth()->user();

        // Ensure the user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can view exam details.'
            ], 403); // HTTP Forbidden
        }

        // Fetch the exam details
        $exam = Exam::with('questions.choices')
                    ->where('id', $exam_id)
                    ->first();

        if (!$exam) {
            return response()->json([
                'error' => 'Exam not found.'
            ], 404); // HTTP Not Found
        }

        // Check if the student is enrolled in the exam
        $isEnrolled = StudentExam::where('user_id', $user->id)
                                ->where('tblschedule_id', $exam_id)
                                ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'error' => 'Unauthorized: You are not enrolled in this exam.'
            ], 403); // HTTP Forbidden
        }

        // Return the exam details with a 200 status code
        return response()->json($exam, 200); // HTTP OK
    }
        public function submitExam(Request $request, $exam_id)
    {
        // Retrieve the authenticated user
        $user = auth()->user();

        // Ensure the user is a student
        if ($user->usertype !== 'student') {
            return response()->json([
                'error' => 'Unauthorized: Only students can submit answers.'
            ], 403); // HTTP Forbidden
        }

        // Validate the request
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:tblquestion,id',
            'answers.*.correctanswer_id' => 'required|exists:correctanswer,id'
        ]);

        // Check if the exam exists and the student is enrolled
        $isEnrolled = StudentExam::where('user_id', $user->id)
                                ->where('tblschedule_id', $exam_id)
                                ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'error' => 'Unauthorized: You are not enrolled in this exam.'
            ], 403); // HTTP Forbidden
        }

        // Process and store the submitted answers
        foreach ($request->input('answers') as $answer) {
            AnsweredQuestion::updateOrCreate(
                [
                    'tblquestion_id' => $answer['question_id'],
                    'tblstudent_id' => $user->id
                ],
                [
                    'correctanswer_id' => $answer['correctanswer_id']
                ]
            );
        }

        // Return a response indicating the submission was successful
        return response()->json([
            'message' => 'Exam submitted successfully.'
        ], 200); // HTTP OK
    }

    public function addExam2(Request $request)
    {
        $request->validate([
            'classtable_id' => 'required|exists:tblclass,id',
            'title' => 'required|string',
            'quarter' => 'required|string',
            'start' => 'required|date_format:Y-m-d H:i:s',
            'end' => 'required|date_format:Y-m-d H:i:s',
            'questions' => 'required|array',
            'questions.*.question_type' => 'required|string',
            'questions.*.question' => 'required|string',
            'questions.*.choices' => 'nullable|array',
            'questions.*.correct_answers' => 'nullable|array',
            'questions.*.correct_answers.*.choice_id' => 'nullable|exists:addchoices,id',
            'questions.*.correct_answers.*.correct_answer' => 'nullable|string',
            'questions.*.correct_answers.*.points' => 'nullable|integer'
        ]);

        // Create exam
        $exam = Exam::create($request->only(['classtable_id', 'title', 'quarter', 'start', 'end']));

        $totalPoints = 0; // Initialize total points
        $totalQuestions = 0; // Initialize total questions

        foreach ($request->input('questions') as $qData) {
            $totalQuestions++; // Increment total questions

            $question = Question::create([
                'tblschedule_id' => $exam->id,
                'question_type' => $qData['question_type'],
                'question' => $qData['question']
            ]);

            if (isset($qData['choices'])) {
                foreach ($qData['choices'] as $choice) {
                    Choice::create([
                        'tblquestion_id' => $question->id,
                        'choices' => $choice
                    ]);
                }
            }

            if (isset($qData['correct_answers'])) {
                foreach ($qData['correct_answers'] as $ansData) {
                    $points = $ansData['points'] ?? 0;
                    $totalPoints += $points; // Add points to total

                    CorrectAnswer::create([
                        'tblquestion_id' => $question->id,
                        'addchoices_id' => $ansData['choice_id'] ?? null,
                        'correct_answer' => $ansData['correct_answer'] ?? null,
                        'points' => $points
                    ]);
                }
            }
        }

    return response()->json([
        'message' => 'Exam created successfully',
        'exam' => $exam,
        'total_points' => $totalPoints, // Return the total points
        'total_questions' => $totalQuestions // Return the total number of questions
    ], 201); // HTTP Created
}

public function viewExamDetails($classtable_id, $exam_id)
{
    // Validate that the class exists
    $class = tblclass::findOrFail($classtable_id);

    // Retrieve the specified exam for the class, including questions, choices, and correct answers
    $exam = Exam::with(['questions.choices', 'questions.correctAnswers'])
        ->where('classtable_id', $classtable_id)
        ->where('id', $exam_id)
        ->first();

    if (!$exam) {
        return response()->json([
            'message' => 'Exam not found for this class'
        ], 404); // HTTP Not Found
    }

    return response()->json([
        'exam' => $exam
    ], 200); // HTTP OK
}

public function viewAllExamsInClass($classtable_id)
{
    // Validate that the class exists
    $class = tblclass::findOrFail($classtable_id);

    // Retrieve all exams for the specified class, including questions, choices, and correct answers
    $exams = Exam::with(['questions.choices', 'questions.correctAnswers'])
        ->where('classtable_id', $classtable_id)
        ->get(); // Use `get()` to retrieve all exams instead of just the first one

    if ($exams->isEmpty()) {
        return response()->json([
            'message' => 'No exams found for this class'
        ], 404); // HTTP Not Found
    }

    return response()->json([
        'exams' => $exams
    ], 200); // HTTP OK
}


}
