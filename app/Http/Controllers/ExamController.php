<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Exam;
use App\Models\joinclass;
use App\Models\Question;
use App\Models\Choice;
use App\Models\CorrectAnswer;
use App\Models\tblclass;
use App\Models\studentexam;
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
            'questions.*.choices.*' => 'string', // Validate each choice
            'questions.*.correct_answers' => 'nullable|array',
            'questions.*.correct_answers.*.correct_answer' => 'nullable|string',
            'questions.*.correct_answers.*.points' => 'nullable|integer',
            'questions.*.correct_answers.*.choice_id' => 'nullable|exists:choices,id', // Validate choice_id
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
    
                // Map to store choice IDs
                $choiceMap = [];
    
                if (isset($qData['choices'])) {
                    foreach ($qData['choices'] as $index => $choice) {
                        $newChoice = Choice::create([
                            'tblquestion_id' => $question->id,
                            'choices' => $choice
                        ]);
                        $choiceMap[$index] = $newChoice->id; // Store the ID in the map
                    }
                }
    
                // Create correct answers if they exist
                if (isset($qData['correct_answers'])) {
                    foreach ($qData['correct_answers'] as $ansData) {
                        $points = $ansData['points'] ?? 0;
                        $totalPoints += $points;
    
                        // Map correct answers to the choice IDs
                        $correctAnswerChoiceId = isset($ansData['id']) ? $choiceMap[$ansData['id']] ?? null : null;
    
                        CorrectAnswer::create([
                            'tblquestion_id' => $question->id,
                            'addchoices_id' => $correctAnswerChoiceId,
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
              'Direction'=> 'required|text',
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
  
              $exam = Exam::create($request->only(['classtable_id', 'title', 'quarter', 'start', 'end',  'Direction']));
              $totalPoints = 0;
              $totalQuestions = 0;
  
              // Create questions and correct answers
             // Create questions and correct answers
            foreach ($request->input('questions') as $qData) {
                $totalQuestions++;
                $question = Question::create([
                    'tblschedule_id' => $exam->id,
                    'question_type' => $qData['question_type'],
                    'question' => $qData['question']
                ]);

               // $choicesMap = []; // Map to keep track of created choices

                if (isset($qData['choices'])) {
                    foreach ($qData['choices'] as $choice) {
                        $choice = Choice::create([
                            'tblquestion_id' => $question->id,
                            'choices' => $choice
                        ]);
                      //  $choicesMap[$choiceText] = $choice->id; // Map choice text to choice ID
                    }
                }

                if (isset($qData['correct_answers'])) {
                    foreach ($qData['correct_answers'] as $ansData) {
                        $points = $ansData['points'] ?? 0;
                        $totalPoints += $points;

                        // Use the choice ID from the mapping, if available
                        //$choiceId = isset($ansData['choice']) ? $choicesMap[$ansData['choice']] ?? null : null;

                        CorrectAnswer::create([
                            'tblquestion_id' => $question->id,
                            'addchoices_id' => $choice->id ?? null,
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

    // View exam details for teacher
    public function viewExamDetails($exam_id)
{
    try {
        // Retrieve the exam with its related questions, choices, and correct answers
        $exam = Exam::with(['questions.choices', 'questions.correctAnswers'])
            ->where('id', $exam_id)
            ->firstOrFail();

        return response()->json([
            'exam' => $exam,
        ], 200); // HTTP OK
    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam details: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exam details. Please try again later.'], 500);
    }
}

public function viewExamDetails2($exam_id)
{
    try {
        // Retrieve the exam with its related questions, choices, and correct answers
        $exam = Exam::with(['questions.choices', 'questions.correctAnswers'])
            ->where('id', $exam_id)
            ->firstOrFail();

        // Calculate the total number of questions (items)
        $totalItems = $exam->questions->count();

        // Calculate the total points (assuming each question has a specific point value)
        // If you have a 'points' field in your 'questions' table, sum it up:
        $totalPoints = $exam->questions->sum('points');

        return response()->json([
            'exam' => $exam,
            'total_items' => $totalItems,
            'total_points' => $totalPoints,
        ], 200); // HTTP OK
    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam details: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exam details. Please try again later.'], 500);
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
    public function viewExam2($exam_id)
    {
        $user = auth()->user();
    
        // Ensure only students can view exams
        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can view exams.'], 403);
        }
    
        // Check if the student is enrolled in the exam
        $isEnrolled = joinclass::where('user_id', $user->id)
            ->exists(); // You may want to add a condition here to check if the student is enrolled in the specific exam
    
        if (!$isEnrolled) {
            return response()->json(['error' => 'Unauthorized: You are not enrolled in this exam.'], 403);
        }
    
        try {
            // Retrieve the exam with questions and choices, but exclude correct answers
            $exam = Exam::with(['questions.choices'])
                ->where('id', $exam_id)
                ->where('status', 1) // Check if the exam is published
                ->firstOrFail();
    
            // Calculate total items
            $totalItems = $exam->questions->count();
    
            // Calculate total points by summing up points from the correctanswer table
            $totalPoints = $exam->questions->sum(function ($question) {
                // Get the correct answer associated with this question
                $correctAnswer = \DB::table('correctanswer')
                    ->where('tblquestion_id', $question->id)
                    ->first();
                
                return $correctAnswer ? $correctAnswer->points : 0; // Assuming 'points' is a field in the 'correctanswer' table
            });
    
            // Shuffle the questions
            $shuffledQuestions = $exam->questions->shuffle();
    
            // Shuffle the choices within each question
            $shuffledQuestions->transform(function ($question) {
                $question->choices = $question->choices->shuffle();
                return $question;
            });
    
            // Attach the shuffled questions (with shuffled choices) back to the exam object
            $exam->questions = $shuffledQuestions;
    
            return response()->json([
                'exam' => $exam,
                'total_items' => $totalItems,
                'total_points' => $totalPoints
            ], 200);
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

        $isEnrolled = joinclass::where('user_id', $user->id)
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

    public function submitExam2(Request $request, $exam_id)
    {
        $user = auth()->user();
    
        // Ensure only students can submit exams
        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can submit exams.'], 403);
        }
    
        // Check if the student is enrolled in the exam
        $isEnrolled = joinclass::where('user_id', $user->id)
           // ->where('tblschedule_id', $exam_id) // Make sure this line is uncommented to check enrollment
            ->exists();
    
        if (!$isEnrolled) {
            return response()->json(['error' => 'Unauthorized: You are not enrolled in this exam.'], 403);
        }
    
        // Check if the student has already submitted the exam
        $hasSubmitted = answeredQuestion::where('users_id', $user->id)
            ->whereHas('question', function ($query) use ($exam_id) {
               $query->where('tblschedule_id', $exam_id);
            })
            ->exists();
    
        if ($hasSubmitted) {
           return response()->json(['error' => 'You have already submitted this exam.'], 403);
        }
    
        // Validate input
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:tblquestion,id',
            'answers.*.addchoices_id' => 'nullable|exists:addchoices,id',
            'answers.*.Student_answer' => 'nullable|string', // Use 'string' instead of 'text' for validation
        ]);
    
        try {
            DB::beginTransaction(); // Start transaction
    
            // Loop through each answer and save/update it
            foreach ($request->input('answers') as $answer) {
                AnsweredQuestion::updateOrCreate(
                    [
                        'users_id' => $user->id,  // Use tblstudent_id instead of user_id
                        'tblquestion_id' => $answer['question_id']
                    ],
                    [
                        'addchoices_id' => $answer['addchoices_id'],  // Optional: Update the answer
                        'Student_answer' => $answer['Student_answer'] // Assuming this is the correct field for student answers
                    ]
                );
            }
    
            DB::commit(); // Commit transaction
    
            return response()->json(['message' => 'Exam submitted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction if there's an error
            Log::error('Failed to submit exam answers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to submit exam answers. Please try again later.'. $e->getMessage()], 500);
        }
    }

    // Get exam results (for students)
    public function getResults(Request $request, $examId)
    {
        $user = auth()->user();
    
        // Check if the user is a student
        if ($user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can view results.'], 403);
        }
    
        try {
            // Retrieve the student's answers for the specific exam
            $results = AnsweredQuestion::where('users_id', $user->id)
                ->whereHas('tblquestion', function ($query) use ($examId) {
                    // $query->where('tblschedule_id', $examId);
                })
                ->with(['tblquestion', 'addchoices']) // Load related question and student's selected choice
                ->get();
    
            // Retrieve all correct answers for the questions in the exam
            $correctAnswers = CorrectAnswer::select('tblquestion_id', 'correct_answer', 'points')
                ->whereIn('tblquestion_id', $results->pluck('tblquestion_id'))
                ->get()
                ->keyBy('tblquestion_id'); // Organize by question ID for easy lookup
    
            // Calculate points per question and total score
            $questionScores = $results->map(function ($result) use ($correctAnswers) {
                // Find the correct answer for this question
                $correctAnswer = $correctAnswers->get($result->tblquestion_id);
    
                // Compare Student's answer with the correct answer
                $isCorrect = $correctAnswer && $result->Student_answer === $correctAnswer->correct_answer;
                $points = $isCorrect ? $correctAnswer->points : 0;
    
                return [
                    'question' => $result->tblquestion->question,
                    'student_answer' => $result->Student_answer, // Assumes this is the actual answer text
                    'correct_answer' => $correctAnswer ? $correctAnswer->correct_answer : null,
                    'points_awarded' => $points, // Display the points awarded for this question
                    'total_possible_points' => $correctAnswer ? $correctAnswer->points : 0, // Display total possible points for this question
                    'is_correct' => $isCorrect
                ];
            });
    
            // Calculate total score
            $totalScore = $questionScores->sum('points_awarded');
    
            return response()->json([
                'results' => $questionScores,
                'total_score' => $totalScore
            ], 200);
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

public function getPublishedExams($class_id) {
    try {
        $exams = Exam::where('classtable_id', $class_id)
                     ->where('is_published', true)
                     ->get();

        return response()->json(['exams' => $exams], 200);
    } catch (\Exception $e) {
        Log::error('Failed to retrieve published exams: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exams.'], 500);
    }
}

    // Archive an exam
    public function archiveExam($exam_id)
    {
        $exam = Exam::findOrFail($exam_id);
        $exam->is_archived = true;
        $exam->save();

        return response()->json(['message' => 'Exam archived successfully']);
    }


    // View all available exams for a student
public function viewAllExams()
{
    $user = auth()->user();

    // Ensure only students can view exams
    if ($user->usertype !== 'student') {
        return response()->json(['error' => 'Unauthorized: Only students can view exams.'], 403);
    }

    try {
        // Retrieve all exams that the student is enrolled in and are published
        $exams = Exam::join('studentexam', 'exams.id', '=', 'studentexam.tblschedule_id')
            ->where('studentexam.user_id', $user->id)
            ->where('exams.status', 1) // Check if the exam is published
            ->select('exams.id', 'exams.title', 'exams.quarter', 'exams.start', 'exams.end')
            ->get();

        if ($exams->isEmpty()) {
            return response()->json(['message' => 'No exams available for you.'], 200);
        }

        return response()->json(['exams' => $exams], 200);

    } catch (\Exception $e) {
        Log::error('Failed to retrieve exams: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exams. Please try again later.'], 500);
    }
}


//inside the class

public function viewAllExams2($class_id)
{
    // Get the authenticated user
    $user = auth()->user();

    // Ensure only students can view exams
    if (!$user || $user->usertype !== 'student') {
        return response()->json(['error' => 'Unauthorized: Only students can view exams.'], 403);
    }

    // Check if class ID is valid
    if (!$class_id || !is_numeric($class_id)) {
        return response()->json(['error' => 'Invalid class ID.'], 400); // Return 400 Bad Request for invalid class ID
    }

    try {
        // Fetch schedules (exams) based on the class ID and student enrollment
        $exams = \DB::table('tblschedule')
            ->join('studentexam', 'tblschedule.id', '=', 'studentexam.tblschedule_id')
            ->join('tblclass', 'tblschedule.classtable_id', '=', 'tblclass.id')
            ->where('studentexam.tblstudent_id', $user->id)
            ->where('tblclass.id', $class_id) // Filter exams by class ID
            ->where('tblschedule.status', 1) // Check if the exam is published
            ->select('tblschedule.id', 'tblschedule.title', 'tblschedule.quarter', 'tblschedule.start', 'tblschedule.end')
            ->get();

        // If no exams (schedules) are found
        if ($exams->isEmpty()) {
            return response()->json(['message' => 'No exams available for you in this class.'], 200);
        }

        // Return the exams (schedules) data
        return response()->json(['exams' => $exams], 200);

    } catch (\Exception $e) {
        // Log the exact error message with context
        \Log::error('Error fetching exams for class ID: ' . $class_id . ' and user ID: ' . $user->id . '. Error: ' . $e->getMessage());

        // Return a generic error message for frontend
        return response()->json(['error' => 'An error occurred while fetching exams. Please try again later.'], 500);  // Internal Server Error
    }
}



public function viewAllExamsInClass2($classtable_id)
{
    try {
        // Ensure the class exists
        $class = tblclass::findOrFail($classtable_id);

        // Retrieve exams that are published (status = 1) and belong to the specified class
        $exams = Exam::with(['questions.correctAnswers'])
            ->where('classtable_id', $classtable_id)
            ->where('status', 1) // Ensure only published exams are shown
            ->get();

        // Check if no exams are found
        if ($exams->isEmpty()) {
            return response()->json(['message' => 'No published exams found for this class'], 404);
        }

        // Process the exams to include total points and total questions
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

}
