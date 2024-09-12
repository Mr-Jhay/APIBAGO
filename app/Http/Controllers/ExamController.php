<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Exam;
use App\Models\instructions;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

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
            $exam = Exam::with(['instruction','questions.choices'])
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
            // Fetch the exam details
            $examSchedule = Exam::where('id', $examId)->firstOrFail();
    
            // Retrieve the student's answers for the specific exam
            $results = AnsweredQuestion::where('users_id', $user->id)
                ->whereHas('tblquestion', function ($query) use ($examId) {
                    // Filter questions by the exam schedule ID
                    $query->where('tblschedule_id', $examId);
                })
                ->with(['tblquestion', 'addchoices']) // Load related question and student's selected choices
                ->get();
    
            // Check if any results were found
            if ($results->isEmpty()) {
                Log::error('Failed to retrieve exam results: No results found.');
                return response()->json(['message' => 'No results found for this exam.'], 404);
            }
    
            // Retrieve all correct answers for the questions in the exam
            $correctAnswers = CorrectAnswer::whereIn('tblquestion_id', $results->pluck('tblquestion_id'))
                ->get()
                ->keyBy('tblquestion_id'); // Organize by question ID for easy lookup
    
            // Calculate points per question and total score
            $questionScores = $results->map(function ($result) use ($correctAnswers) {
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
    
            // Calculate total score and total possible points
            $totalScore = $questionScores->sum('points_awarded');
            $totalPossiblePoints = $questionScores->sum('total_possible_points');
    
            // Calculate passing or failing status
            $passingThreshold = $totalPossiblePoints * 0.50; // 50% of total possible points
            $status = $totalScore >= $passingThreshold ? 1 : 0;
    
            // Save or update the result in tblresult
            DB::table('tblresult')->updateOrInsert(
                ['users_id' => $user->id, 'exam_id' => $examId],
                [
                    'total_score' => $totalScore,
                    'total_exam' => $totalPossiblePoints,
                    'status' => $status
                ]
            );
    
            return response()->json([
                'results' => $questionScores,
                'total_score' => $totalScore,
                'total_possible_points' => $totalPossiblePoints,
                'status' => $status // Include pass/fail status
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



public function getResults2(Request $request)
{
    try {
        // Get the authenticated user
        $user = Auth::user();
        
        // Retrieve results specific to the authenticated user
        $results = DB::table('tblresult')
            ->join('users', 'tblresult.users_id', '=', 'users.id')
            ->join('tblschedule', 'tblresult.exam_id', '=', 'tblschedule.id')
            ->select(
                'tblresult.id',
                'users.lname AS student_name',
                'tblschedule.title AS exam_title',
                'tblresult.total_score',
                'tblresult.total_exam',
                'tblresult.status',
                'tblresult.created_at',
                'tblresult.updated_at'
            )
            ->where('tblresult.users_id', $user->id)  // Filter by authenticated user
            ->get()
            ->map(function ($result) {
                // Transform status code to meaningful labels
                $result->status = $result->status == 1 ? 'Passed' : 'Failed';
                return $result;
            });

        // Check if results are empty
        if ($results->isEmpty()) {
            return response()->json(['message' => 'No exam results found for this user.'], 404);
        }

        return response()->json($results, 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to retrieve exam results. Please try again later.',
            'exception' => $e->getMessage()
        ], 500);
    }
}
//pag create ng schedule
public function createExam(Request $request)
{
    $request->validate([
        'classtable_id' => 'required|exists:tblclass,id',
        'title' => 'required|string',
        'quarter' => 'required|string',
        'start' => 'required|date_format:Y-m-d H:i:s',
        'end' => 'required|date_format:Y-m-d H:i:s',
        'points_exam' => 'required|numeric',
    ]);

    try {
        // Create the exam
        $exam = Exam::create($request->only(['classtable_id', 'title', 'quarter', 'start', 'end', 'points_exam']));

        return response()->json([
            'message' => 'Exam created successfully',
            'exam' => $exam
        ], 201);
    } catch (\Exception $e) {
        Log::error('Failed to create exam: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to create exam.'], 500);
    }
}


//add questions to the exam 
public function addQuestionsToExam(Request $request, $examId)
{
    $request->validate([
        'instructions' => 'required|array',
        'instructions.*.instruction' => 'required|string',
        'instructions.*.question_type' => 'required|string',
        'instructions.*.questions' => 'required|array',
        'instructions.*.questions.*.question' => 'required|string',
        'instructions.*.questions.*.choices' => 'nullable|array',
        'instructions.*.questions.*.choices.*' => 'string',
        'instructions.*.questions.*.correct_answers' => 'nullable|array',
        'instructions.*.questions.*.correct_answers.*.correct_answer' => 'nullable|string',
        'instructions.*.questions.*.correct_answers.*.points' => 'nullable|integer',
    ]);

    try {
        DB::beginTransaction();

        $exam = Exam::findOrFail($examId);

        $totalPoints = 0;
        $totalQuestions = 0;
        $groupedQuestions = [];

        foreach ($request->input('instructions') as $instructionData) {
            $instruction = Instructions::create([
                'schedule_id' => $examId,
                'instruction' => $instructionData['instruction'],
                'question_type' => $instructionData['question_type'],
            ]);

            $groupedQuestions[$instructionData['question_type']] = [];

            foreach ($instructionData['questions'] as $qData) {
                $totalQuestions++;

                $question = Question::create([
                    'tblschedule_id' => $exam->id,
                    'question' => $qData['question'],
                ]);

                $choiceMap = [];

                if (isset($qData['choices'])) {
                    foreach ($qData['choices'] as $index => $choice) {
                        $newChoice = Choice::create([
                            'tblquestion_id' => $question->id,
                            'choices' => $choice
                        ]);
                        $choiceMap[$index] = $newChoice->id;
                    }
                }

                if (isset($qData['correct_answers'])) {
                    foreach ($qData['correct_answers'] as $ansData) {
                        $points = $ansData['points'] ?? 0;
                        $totalPoints += $points;

                        $correctAnswerChoiceId = null;
                        if (isset($qData['choices'])) {
                            foreach ($qData['choices'] as $index => $choice) {
                                if ($choice === $ansData['correct_answer']) {
                                    $correctAnswerChoiceId = $choiceMap[$index] ?? null;
                                    break;
                                }
                            }
                        }

                        CorrectAnswer::create([
                            'tblquestion_id' => $question->id,
                            'addchoices_id' => $correctAnswerChoiceId,
                            'correct_answer' => $ansData['correct_answer'] ?? null,
                            'points' => $points
                        ]);
                    }
                }

                $groupedQuestions[$instructionData['question_type']][] = $question;
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Questions and instructions added successfully',
            'total_points' => $totalPoints,
            'total_questions' => $totalQuestions,
            'grouped_questions' => $groupedQuestions,
            'submitted_data' => $request->all() // Return the setup body here
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to add questions and instructions: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to add questions and instructions.'], 500);
    }
}




//result sa test bank
public function getResultswithtestbank(Request $request, $examId)
{
    $user = auth()->user();

    // Check if the user is a student
    if ($user->usertype !== 'student') {
        return response()->json(['error' => 'Unauthorized: Only students can view results.'], 403);
    }

    try {
        // Fetch the exam details
        $examSchedule = Exam::where('id', $examId)->firstOrFail();

        // Retrieve the student's answers for the specific exam
        $results = AnsweredQuestion::where('users_id', $user->id)
            ->whereHas('tblquestion', function ($query) use ($examId) {
                // Filter questions by the exam schedule ID
                $query->where('tblschedule_id', $examId);
            })
            ->with(['tblquestion', 'addchoices']) // Load related question and student's selected choices
            ->get();

        // Check if any results were found
        if ($results->isEmpty()) {
            Log::error('Failed to retrieve exam results: No results found.');
            return response()->json(['message' => 'No results found for this exam.'], 404);
        }

        // Retrieve all correct answers for the questions in the exam
        $correctAnswers = CorrectAnswer::whereIn('tblquestion_id', $results->pluck('tblquestion_id'))
            ->get()
            ->keyBy('tblquestion_id'); // Organize by question ID for easy lookup

        // Calculate points per question and total score
        $questionScores = $results->map(function ($result) use ($correctAnswers) {
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

        // Calculate total score and use points_exam for total possible points
        $totalScore = $questionScores->sum('points_awarded');
        $totalPossiblePoints = $examSchedule->points_exam; // Use points_exam from the examSchedule

        // Calculate passing or failing status
        $passingThreshold = $totalPossiblePoints * 0.50; // 50% of total possible points
        $status = $totalScore >= $passingThreshold ? 1 : 0;

        // Save or update the result in tblresult
        DB::table('tblresult')->updateOrInsert(
            ['users_id' => $user->id, 'exam_id' => $examId],
            [
                'total_score' => $totalScore,
                'total_exam' => $totalPossiblePoints,
                'status' => $status
            ]
        );

        return response()->json([
            'results' => $questionScores,
            'total_score' => $totalScore,
            'total_possible_points' => $totalPossiblePoints,
            'status' => $status // Include pass/fail status
        ], 200);
    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam results: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exam results. Please try again later.'], 500);
    }
}




public function viewExam2updated($exam_id)
{
    $user = auth()->user();

    // Ensure only students can view exams
    if ($user->usertype !== 'student') {
        return response()->json(['error' => 'Unauthorized: Only students can view exams.'], 403);
    }

    // Check if the student is enrolled in the exam
    $isEnrolled = joinclass::where('user_id', $user->id)
        ->exists(); // Add a condition if needed to check if the student is enrolled in the specific exam

    if (!$isEnrolled) {
        return response()->json(['error' => 'Unauthorized: You are not enrolled in this exam.'], 403);
    }

    try {
        // Retrieve the exam with questions and choices, but exclude correct answers
        $exam = Exam::with(['instructions.questions.choices'])
            ->where('id', $exam_id)
            ->where('status', 1) // Check if the exam is published
            ->firstOrFail();


        // Initialize variables for tracking points and questions
        $totalPoints = 0;
        $selectedQuestions = collect();

        // Iterate over the questions, stopping when the points_exam limit is reached
        foreach ($exam->questions as $question) {
            // Get the correct answer associated with this question
            $correctAnswer = \DB::table('correctanswer')
                ->where('tblquestion_id', $question->id)
                ->first();

            $questionPoints = $correctAnswer ? $correctAnswer->points : 0;

            // Check if adding this question exceeds the points_exam limit
            if ($totalPoints + $questionPoints > $exam->points_exam) {
                break; // Stop adding questions if the limit is reached
            }

            $totalPoints += $questionPoints;
            $selectedQuestions->push($question);
        }

        // Shuffle the selected questions
        $shuffledQuestions = $selectedQuestions->shuffle();

        // Shuffle the choices within each question
        $shuffledQuestions->transform(function ($question) {
            $question->choices = $question->choices->shuffle();
            return $question;
        });

        // Attach the shuffled questions (with shuffled choices) back to the exam object
        $exam->questions = $shuffledQuestions;

        return response()->json([
            'exam' => $exam,
            'total_items' => $shuffledQuestions->count(),
            'total_points' => $totalPoints
        ], 200);
    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam details: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exam details. Please try again later.' . $e->getMessage()], 500);
    }
}
























public function getAllExamsByClass($classId)
{
    try {
        // Fetch exams filtered by the class ID
        $exams = Exam::where('classtable_id', $classId)->get();

        // Check if any exams were found
        if ($exams->isEmpty()) {
            return response()->json([
                'message' => 'No exams found for this class.'
            ], 404);
        }

        return response()->json([
            'exams' => $exams
        ], 200);
    } catch (\Exception $e) {
        Log::error('Failed to retrieve exams for class: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exams.'], 500);
    }
}


public function getExam($id)
{
    try {
        $exam = Exam::findOrFail($id);

        return response()->json([
            'exam' => $exam
        ], 200);
    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam: ' . $e->getMessage());
        return response()->json(['error' => 'Exam not found.'], 404);
    }
}


public function updateExam(Request $request, $examId)
{
    $request->validate([
        'title' => 'nullable|string',
        'quarter' => 'nullable|string',
        'start' => 'nullable|date_format:Y-m-d H:i:s',
        'end' => 'nullable|date_format:Y-m-d H:i:s',
        'points_exam' => 'nullable|numeric',
        'instruction' => 'nullable|string',
        'questions' => 'nullable|array',
        'questions.*.id' => 'nullable|exists:questions,id',
        'questions.*.question_type' => 'required|string',
        'questions.*.question' => 'required|string',
        'questions.*.choices' => 'nullable|array',
        'questions.*.choices.*' => 'string',
        'questions.*.correct_answers' => 'nullable|array',
        'questions.*.correct_answers.*.correct_answer' => 'nullable|string',
        'questions.*.correct_answers.*.points' => 'nullable|integer',
        'questions.*.correct_answers.*.choice_id' => 'nullable|exists:choices,id',
    ]);

    try {
        DB::beginTransaction();

        $exam = Exam::findOrFail($examId); // Ensure the exam exists

        // Update exam details if provided
        $exam->update($request->only(['title', 'quarter', 'start', 'end', 'points_exam']));

        $totalPoints = 0;
        $totalQuestions = 0;

        // Update instruction if provided
        if ($request->has('instruction')) {
            instructions::updateOrCreate(
                ['schedule_id' => $examId],
                ['instruction' => $request->input('instruction')]
            );
        }

        // Process each question
        foreach ($request->input('questions', []) as $qData) {
            $totalQuestions++;

            if (isset($qData['id'])) {
                // Update existing question
                $question = Question::findOrFail($qData['id']);
                $question->update([
                    'question_type' => $qData['question_type'],
                    'question' => $qData['question']
                ]);
            } else {
                // Create new question
                $question = Question::create([
                    'tblschedule_id' => $exam->id,
                    'question_type' => $qData['question_type'],
                    'question' => $qData['question']
                ]);
            }

            // Map to store choice IDs
            $choiceMap = [];

            if (isset($qData['choices'])) {
                foreach ($qData['choices'] as $index => $choice) {
                    // Update or create choices
                    $choiceRecord = Choice::updateOrCreate(
                        ['tblquestion_id' => $question->id, 'choices' => $choice],
                        ['tblquestion_id' => $question->id, 'choices' => $choice]
                    );
                    $choiceMap[$index] = $choiceRecord->id; // Store the ID in the map
                }
            }

            // Handle correct answers
            if (isset($qData['correct_answers'])) {
                foreach ($qData['correct_answers'] as $ansData) {
                    $points = $ansData['points'] ?? 0;
                    $totalPoints += $points;

                    // Map correct answers to the choice IDs
                    $correctAnswerChoiceId = isset($ansData['choice_id']) ? $choiceMap[$ansData['choice_id']] ?? null : null;

                    // Update or create correct answer
                    CorrectAnswer::updateOrCreate(
                        [
                            'tblquestion_id' => $question->id,
                            'addchoices_id' => $correctAnswerChoiceId
                        ],
                        [
                            'correct_answer' => $ansData['correct_answer'] ?? null,
                            'points' => $points
                        ]
                    );
                }
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Exam and related data updated successfully',
            'total_points' => $totalPoints,
            'total_questions' => $totalQuestions
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to update exam and related data: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to update exam and related data.'], 500);
    }
}



public function getExamInstructionAndCorrectAnswers($examId)
{
    try {
        $exam = Exam::findOrFail($examId);
        $instructions = Instructions::where('schedule_id', $examId)->with('questions.choices', 'questions.correctAnswers')->get();

        return response()->json([
            'exam' => $exam,
            'instructions' => $instructions,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam questions: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exam questions.'], 500);
    }
}











public function updateQuestionsInExam(Request $request, $examId)
{
    $request->validate([
        'title' => 'nullable|string',
        'quarter' => 'nullable|string',
        'start' => 'nullable|date_format:Y-m-d H:i:s',
        'end' => 'nullable|date_format:Y-m-d H:i:s',
        'points_exam' => 'nullable|numeric',
        'instructions' => 'nullable|array',
        'instructions.*.id' => 'required|exists:instructions,id',
        'instructions.*.instruction' => 'nullable|string',
        'instructions.*.question_type' => 'nullable|string',
        'questions' => 'nullable|array',
        'questions.*.id' => 'nullable|exists:questions,id',
        'questions.*.question_type' => 'required|string',
        'questions.*.question' => 'required|string',
        'questions.*.choices' => 'nullable|array',
        'questions.*.choices.*' => 'string',
        'questions.*.correct_answers' => 'nullable|array',
        'questions.*.correct_answers.*.id' => 'nullable|exists:correct_answers,id',
        'questions.*.correct_answers.*.correct_answer' => 'nullable|string',
        'questions.*.correct_answers.*.points' => 'nullable|integer',
        'questions.*.correct_answers.*.choice_id' => 'nullable|exists:choices,id',
    ]);

    try {
        DB::beginTransaction();

        $exam = Exam::findOrFail($examId);

        // Update exam details if provided
        $exam->update($request->only(['title', 'quarter', 'start', 'end', 'points_exam']));

        $totalPoints = 0;
        $totalQuestions = 0;

        // Update instructions if provided
        if ($request->has('instructions')) {
            foreach ($request->input('instructions') as $instData) {
                $instruction = Instruction::findOrFail($instData['id']);
                $instruction->update([
                    'instruction' => $instData['instruction'] ?? $instruction->instruction,
                    'question_type' => $instData['question_type'] ?? $instruction->question_type
                ]);
            }
        }

        // Process each question
        foreach ($request->input('questions', []) as $qData) {
            $totalQuestions++;

            if (isset($qData['id'])) {
                // Update existing question
                $question = Question::findOrFail($qData['id']);
                $question->update([
                    'question_type' => $qData['question_type'],
                    'question' => $qData['question']
                ]);
            } else {
                // Create new question
                $question = Question::create([
                    'tblschedule_id' => $exam->id,
                    'question_type' => $qData['question_type'],
                    'question' => $qData['question']
                ]);
            }

            // Map to store choice IDs
            $choiceMap = [];

            if (isset($qData['choices'])) {
                foreach ($qData['choices'] as $index => $choice) {
                    // Update or create choices
                    $choiceRecord = Choice::updateOrCreate(
                        ['tblquestion_id' => $question->id, 'choices' => $choice],
                        ['tblquestion_id' => $question->id, 'choices' => $choice]
                    );
                    $choiceMap[$index] = $choiceRecord->id; // Store the ID in the map
                }
            }

            // Handle correct answers
            if (isset($qData['correct_answers'])) {
                foreach ($qData['correct_answers'] as $ansData) {
                    $points = $ansData['points'] ?? 0;
                    $totalPoints += $points;

                    // Map correct answers to the choice IDs
                    $correctAnswerChoiceId = isset($ansData['choice_id']) ? $choiceMap[$ansData['choice_id']] ?? null : null;

                    // Update or create correct answer
                    CorrectAnswer::updateOrCreate(
                        [
                            'tblquestion_id' => $question->id,
                            'addchoices_id' => $correctAnswerChoiceId
                        ],
                        [
                            'correct_answer' => $ansData['correct_answer'] ?? null,
                            'points' => $points
                        ]
                    );
                }
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Exam and related data updated successfully',
            'total_points' => $totalPoints,
            'total_questions' => $totalQuestions
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to update exam and related data: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to update exam and related data.'], 500);
    }
}



}
