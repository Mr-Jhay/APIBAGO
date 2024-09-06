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
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Mail\TestMail;
use App\Mail\WelcomeMail;

class ExamController extends Controller
{
    // Create an exam with associated questions and correct answers
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
            'questions.*.correct_answers.*.points' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $exam = Exam::create($request->only(['classtable_id', 'title', 'quarter', 'start', 'end']));
            $totalPoints = 0;
            $totalQuestions = 0;

            // Create questions and correct answers
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

      // Create an exam with additional logic - addExam2
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
              'questions.*.correct_answers.*.points' => 'nullable|integer',
              'notify_students' => 'nullable|boolean',  // Example of extra logic
          ]);
  
          try {
              DB::beginTransaction();
  
              $exam = Exam::create($request->only(['classtable_id', 'title', 'quarter', 'start', 'end']));
              $totalPoints = 0;
              $totalQuestions = 0;
  
              // Create questions and correct answers
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
  
              // Notify students if required (Extra logic for addExam2)
              if ($request->input('notify_students', false)) {
                  $class = tblclass::find($exam->classtable_id);
                  $students = DB::table('users')
                      ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                      ->where('joinclass.class_id', $class->id)
                      ->where('joinclass.status', 1)
                      ->where('users.usertype', 'student')
                      ->select('users.email')
                      ->get();
  
                  foreach ($students as $student) {
                      Mail::to($student->email)->send(new WelcomeMail($student->email));
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
              Log::error('Failed to create exam (addExam2): ' . $e->getMessage());
              return response()->json(['error' => 'Failed to create exam.'], 500);
          }
      }
  
    // Publish Exam
    public function publish($exam_id) {
        try {
            // Find the exam by ID
            $exam = Exam::find($exam_id);
    
            // If exam doesn't exist, return a 404 error
            if (!$exam) {
                return response()->json(['error' => 'Exam not found'], 404);
            }
    
            // Publish the exam
            $exam->status = 1;
            $exam->save();
    
            // Retrieve class_id from the exam
            $class_id = $exam->classtable_id;
    
            // Get students enrolled in the class
            $students = DB::table('users')
                ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                ->where('joinclass.class_id', $class_id)
                ->where('joinclass.status', 1)
                ->where('users.usertype', 'student')
                ->select('users.id', 'users.fname', 'users.email')
                ->get();
    
            // Send welcome email to each student
            foreach ($students as $student) {
                Mail::to($student->email)->send(new WelcomeMail($student->fname));
            }
    
            // Return success response
            return response()->json(['message' => 'Exam published and welcome emails sent successfully'], 200);
    
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Failed to publish exam: ' . $e->getMessage());
    
            // Return a 500 Internal Server Error
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    
    public function publish2($exam_id)
    {
        try {
            // Find the exam by ID
            $exam = Exam::find($exam_id);

            // If exam doesn't exist, return a 404 error
            if (!$exam) {
                return response()->json(['error' => 'Exam not found'], 404);
            }

            // Publish the exam
            $exam->status = 1;
            $exam->save();

            // Return success response
            return response()->json(['message' => 'Exam published successfully'], 200);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Failed to publish exam: ' . $e->getMessage());

            // Return a 500 Internal Server Error
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    // View all exams for a specific class
    public function viewAllExamsInClass($classtable_id)
    {
        try {
            $class = tblclass::findOrFail($classtable_id);
            $exams = Exam::with(['questions.correctAnswers'])
                ->where('classtable_id', $classtable_id)
                ->get();

            if ($exams->isEmpty()) {
                return response()->json(['message' => 'No exams found for this class'], 404);
            }

            $examsWithDetails = $exams->map(function ($exam) {
                $totalPoints = $exam->questions->reduce(function ($carry, $question) {
                    return $carry + $question->correctAnswers->sum('points');
                }, 0);

                $totalQuestions = $exam->questions->count();

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'quarter' => $exam->quarter,
                    'start' => $exam->start,
                    'end' => $exam->end,
                    'status' => $exam->status,
                    'total_points' => $totalPoints,
                    'total_questions' => $totalQuestions
                ];
            });

            return response()->json(['exams' => $examsWithDetails], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve exams: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error. Please try again later.'], 500);
        }
    }

    // View exam details for students
    public function viewExam($exam_id)
    {
        $user = auth()->user();

        // Ensure only students can view exams
        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can view exams.'], 403);
        }

        // Check if the student is enrolled in the exam
        $isEnrolled = StudentExam::where('user_id', $user->id)
            ->where('tblschedule_id', $exam_id)
            ->exists();

        if (!$isEnrolled) {
            return response()->json(['error' => 'Unauthorized: You are not enrolled in this exam.'], 403);
        }

        try {
            // Retrieve the exam with questions and choices, but exclude correct answers
            $exam = Exam::with(['questions.choices'])
                ->where('id', $exam_id)
                ->where('status', 1) // Check if the exam is published
                ->firstOrFail();

            return response()->json(['exam' => $exam], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve exam details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve exam details. Please try again later.'], 500);
        }
    }

    // Submit exam answers (for students)
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
            'answers.*.correctanswer_id' => 'required|exists:correctanswer,id',
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

    // Get exam results (for students)
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

    // Fetch all exams for a specific class
    public function getExams($class_id)
    {
        $exams = Exam::where('class_id', $class_id)->get();
        return response()->json(['exams' => $exams]);
    }

    // Publish an exam
    public function publishExam($exam_id)
    {
        $exam = Exam::findOrFail($exam_id);
        $exam->is_published = true;
        $exam->save();

        // Optionally, notify students via email
        // Code for notification goes here

        return response()->json(['message' => 'Exam published successfully']);
    }

    // Fetch only published exams for a specific class (for students)
    public function getPublishedExams($class_id)
    {
        $exams = Exam::where('class_id', $class_id)
                     ->where('is_published', true)
                     ->get();

        return response()->json(['exams' => $exams]);
    }

    // Archive an exam
    public function archiveExam($exam_id)
    {
        $exam = Exam::findOrFail($exam_id);
        $exam->is_archived = true;
        $exam->save();

        return response()->json(['message' => 'Exam archived successfully']);
    }
}
