<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Choice;
use App\Models\CorrectAnswer;
use App\Models\tblclass;
use App\Models\StudentExam;
use App\Models\AnsweredQuestion;

class ExamController extends Controller
{
    // Add Exam
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
            'questions.*.correct_answers.*.correct_answer' => 'nullable|string',
            'questions.*.correct_answers.*.points' => 'nullable|integer'
        ]);

        try {
            // Create exam
            DB::beginTransaction();

            $exam = Exam::create($request->only(['classtable_id', 'title', 'quarter', 'start', 'end']));

            $totalPoints = 0;
            $totalQuestions = 0;

            foreach ($request->input('questions') as $qData) {
                $totalQuestions++;

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
                        $totalPoints += $points;

                        CorrectAnswer::create([
                            'tblquestion_id' => $question->id,
                            'addchoices_id' => $ansData['choice_id'] ?? null,
                            'correct_answer' => $ansData['correct_answer'] ?? null,
                            'points' => $points
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Exam created successfully',
                'exam' => $exam,
                'total_points' => $totalPoints,
                'total_questions' => $totalQuestions
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create exam: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create exam.'], 500);
        }
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
        'questions.*.correct_answers.*.correct_answer' => 'nullable|string',
        'questions.*.correct_answers.*.points' => 'nullable|integer'
    ]);

    try {
        // Begin database transaction
        DB::beginTransaction();

        // Create the exam
        $exam = Exam::create($request->only(['classtable_id', 'title', 'quarter', 'start', 'end']));

        $totalPoints = 0;
        $totalQuestions = count($request->input('questions'));

        // Iterate through the questions in the request
        foreach ($request->input('questions') as $qData) {
            // Create each question
            $question = Question::create([
                'tblschedule_id' => $exam->id,
                'question_type' => $qData['question_type'],
                'question' => $qData['question']
            ]);

            // Create each choice if it exists
            if (isset($qData['choices'])) {
                foreach ($qData['choices'] as $choice) {
                    Choice::create([
                        'tblquestion_id' => $question->id,
                        'choices' => $choice
                    ]);
                }
            }

            // Create each correct answer if it exists
            if (isset($qData['correct_answers'])) {
                foreach ($qData['correct_answers'] as $ansData) {
                    $points = $ansData['points'] ?? 0;
                    $totalPoints += $points; // Accumulate the total points

                    CorrectAnswer::create([
                        'tblquestion_id' => $question->id,
                        'addchoices_id' => $ansData['choice_id'] ?? null,
                        'correct_answer' => $ansData['correct_answer'] ?? null,
                        'points' => $points
                    ]);
                }
            }
        }

        // Commit the transaction
        DB::commit();

        return response()->json([
            'message' => 'Exam created successfully',
            'exam' => $exam,
            'total_points' => $totalPoints,      // Ensure these are included
            'total_questions' => $totalQuestions // Ensure these are included
        ], 201);
    } catch (\Exception $e) {
        // Rollback the transaction if something goes wrong
        DB::rollBack();
        Log::error('Failed to create exam in addExam2: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to create exam.'], 500);
    }
}

    // View All Exams for a Specific Class/Subject
   public  function viewAllExamsInClass($classtable_id)
    {
        try {
            $class = tblclass::findOrFail($classtable_id);
            $exams = Exam::with(['questions'])
                ->where('classtable_id', $classtable_id)
                ->get();

            if ($exams->isEmpty()) {
                return response()->json(['message' => 'No exams found for this class'], 404);
            }

            return response()->json(['exams' => $exams], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve exams: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error. Please try again later.'], 500);
        }
    }

    // View Exam Details for Teachers
    public function viewExamForTeacher($exam_id)
    {
        try {
            $exam = Exam::with(['questions.choices', 'questions.correctAnswers'])
                ->findOrFail($exam_id);
            return response()->json(['exam' => $exam], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Exam not found: ' . $e->getMessage());
            return response()->json(['error' => 'Exam not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve exam details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve exam details.'], 500);
        }
    }
    


    // View Exam Details for Students
    public function viewExam($exam_id)
{
    $user = auth()->user();

    if ($user->usertype !== 'student') {
        return response()->json(['error' => 'Unauthorized: Only students can view exams.'], 403);
    }

    $isEnrolled = StudentExam::where('user_id', $user->id)
                             ->where('tblschedule_id', $exam_id)
                             ->exists();

    if (!$isEnrolled) {
        return response()->json(['error' => 'Unauthorized: You are not enrolled in this exam.'], 403);
    }

    try {
        $exam = Exam::with(['questions.choices', 'questions.correctAnswers'])
                    ->findOrFail($exam_id);

        return response()->json(['exam' => $exam], 200);
    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam details: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exam details. Please try again later.'], 500);
    }
}

    

    // Submit Exam Answers (For Students)
    public function submitExam(Request $request, $exam_id)
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
            'answers.*.question_id' => 'required|exists:tblquestion,id',
            'answers.*.correctanswer_id' => 'required|exists:correctanswer,id'
        ]);
    
        try {
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
    
            return response()->json(['message' => 'Exam submitted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to submit exam answers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to submit exam answers. Please try again later.'], 500);
        }
    }
    

    // Get Exam Results (For Students)
    public function getResults(Request $request, $examId)
    {
        $user = auth()->user();

        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can view results.'], 403);
        }

        try {
            $results = AnsweredQuestion::where('users_id', $user->id)
                ->whereHas('question', function ($query) use ($examId) {
                    $query->where('tblschedule_id', $examId);
                })
                ->with('question', 'correctAnswer')
                ->get();

            $score = $results->filter(function ($result) {
                return $result->correctAnswer->addchoices_id === $result->correctanswer_id;
            })->count();

            return response()->json(['results' => $results, 'score' => $score], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve exam results: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve exam results. Please try again later.'], 500);
        }
    }

    // Update an Existing Exam
    public function updateExam(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'quarter' => 'required|string|max:50',
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        try {
            $exam = Exam::findOrFail($id);

            $exam->update([
                'title' => $request->title,
                'quarter' => $request->quarter,
                'start' => $request->start,
                'end' => $request->end
            ]);

            return response()->json(['message' => 'Exam updated successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update exam: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update exam. Please try again later.'], 500);
        }
    }

    // Delete an Exam
    public function deleteExam($id)
    {
        try {
            $exam = Exam::findOrFail($id);
            $exam->delete();

            return response()->json(['message' => 'Exam deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete exam: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete exam. Please try again later.'], 500);
        }
    }

    // Start an Exam (For Students)
    public function startExam(Request $request, $examId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();

        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can start exams.'], 403);
        }

        $existingExam = StudentExam::where('user_id', $user->id)
            ->where('tblschedule_id', $examId)
            ->first();

        if ($existingExam) {
            return response()->json(['message' => 'Exam already started.'], 200);
        }

        try {
            $studentExam = StudentExam::create([
                'user_id' => $user->id,
                'tblschedule_id' => $examId,
            ]);

            return response()->json(['message' => 'Exam started successfully.', 'student_exam_id' => $studentExam->id], 201);
        } catch (\Exception $e) {
            Log::error('Failed to start exam: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to start exam. Please try again later.'], 500);
        }
    }

    // View All Exams the Student is Enrolled In
    public function getStudentExams()
    {
        $user = auth()->user();

        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can view their exams.'], 403);
        }

        try {
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

            return response()->json($exams, 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve student exams: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve student exams. Please try again later.'], 500);
        }
    }

    // View Specific Exam Details for Students
    public function getExamDetails($exam_id)
    {
        $user = auth()->user();

        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can view exam details.'], 403);
        }

        try {
            $exam = Exam::with('questions.choices')
                        ->where('id', $exam_id)
                        ->firstOrFail();

            $isEnrolled = StudentExam::where('user_id', $user->id)
                                     ->where('tblschedule_id', $exam_id)
                                     ->exists();

            if (!$isEnrolled) {
                return response()->json(['error' => 'Unauthorized: You are not enrolled in this exam.'], 403);
            }

            return response()->json($exam, 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve exam details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve exam details. Please try again later.'], 500);
        }
    }

    // ExamController.php

// Archive an Exam (soft delete)
public function archiveExam($id)
{
    try {
        $exam = Exam::findOrFail($id);
        $exam->delete(); // Assuming soft deletes are enabled
        return response()->json(['message' => 'Exam archived successfully'], 200);
    } catch (\Exception $e) {
        Log::error('Failed to archive exam: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to archive exam. Please try again later.'], 500);
    }
}

     // Publish Exam
     public function publishExam($examId)
     {
         try {
             $exam = Exam::findOrFail($examId);
             
             // Check if the exam is already published
             if ($exam->is_published) {
                 return response()->json(['message' => 'Exam is already published.'], 200);
             }
 
             // Publish the exam
             $exam->is_published = true;
             $exam->save();
 
             return response()->json(['message' => 'Exam published successfully'], 200);
         } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             Log::error('Exam not found: ' . $e->getMessage());
             return response()->json(['error' => 'Exam not found.'], 404);
         } catch (\Exception $e) {
             Log::error('Failed to publish exam: ' . $e->getMessage());
             return response()->json(['error' => 'Failed to publish exam.'], 500);
         }
     }


}
