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
use App\Models\tblbank;
use App\Models\studentexam;
use App\Models\AnsweredQuestion;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Mail\TestMail;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Carbon\Carbon;

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
    
    public function archiveExam($id)
    {
        try {
            // Find the exam by its ID
            $exam = Exam::findOrFail($id);

            // Mark the exam as archived (assuming you have an 'archived' column in your table)
            $exam->archived = true;

            // Save the updated exam
            $exam->save();

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Exam archived successfully.',
            ], 200);

        } catch (\Exception $e) {
            // Return error response if something goes wrong
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive exam: ' . $e->getMessage(),
            ], 500);
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
    public function publish($exam_id, Request $request) {
        // Validate the 'name' field from the request
        $request->validate([
            'name' => 'required|string'
        ]);
    
        try {
            $name = $request->input('name');
    
            // Find the exam by ID
            $exam = Exam::find($exam_id);
    
            // If exam doesn't exist, return a 404 error
            if (!$exam) {
                return response()->json(['error' => 'Exam not found'], 404);
            }
    
            // Publish the exam (set status to 1 for 'published')
            $exam->status = 1;
            $exam->save();
    
            // Retrieve class_id from the exam
            $class_id = $exam->classtable_id;
    
            // Get students enrolled in the class
            $students = DB::table('users')
                ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
                ->where('joinclass.class_id', $class_id)
                ->where('joinclass.status', 1)  // Ensure only active students
                ->where('users.usertype', 'student')
                ->select('users.email')  // Select only the email
                ->get();
    
            // Send an email to each student
            foreach ($students as $student) {
                Mail::to($student->email)->send(new WelcomeMail($name));  // Pass the name to the mail
            }
    
            // Return success response
            return response()->json(['message' => 'Exam published and emails sent successfully'], 200);
    
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
        // Get the authenticated user
        $user = auth()->user();

        // Ensure only students can view exams
        if (!$user || $user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can view exams.'], 403);
        }

        // Ensure the class exists
        $class = tblclass::findOrFail($classtable_id);

        // Retrieve exams that are published (status = 1) and belong to the specified class
        $exams = \DB::table('tblschedule')
            ->leftJoin('tblresult', function ($join) use ($user) {
                $join->on('tblschedule.id', '=', 'tblresult.exam_id')
                     ->where('tblresult.users_id', '=', $user->id);  // Filter by authenticated user
            })
            ->where('tblschedule.classtable_id', $classtable_id)
            ->where('tblschedule.status', 1) // Only published exams
            ->select(
                'tblschedule.id',
                'tblschedule.title',
                'tblschedule.quarter',
                'tblschedule.start',
                'tblschedule.end',
                'tblresult.total_score', // Include the student's result score
                'tblresult.status' // Include the result status from tblresult
            )
            ->orderBy('tblschedule.created_at', 'desc')
            ->get()
            ->map(function ($exam) use ($user) {
                $currentDateTime = now(); // Get the current date and time

                // Convert total_score to whole number or mark as "Unfinished" if null
                $exam->total_score = $exam->total_score === null ? 'Unfinished' : (int)round($exam->total_score);

                // Determine the status based on tblresult
                if ($exam->status === null) {
                    $exam->status = 'Pending';  // No status in tblresult means "Pending"
                } else {
                    // Use the status from tblresult (e.g., 1 for "Passed", 0 for "Failed")
                    $exam->status = $exam->status == 1 ? 'Passed' : 'Failed';
                }

                // If the result exists, mark the exam as "Done"
                if ($exam->total_score !== 'Unfinished') {
                    $exam->status = 'Finish';
                }

                // If no result and the exam's end time has passed, mark it as "Missing"
                if ($currentDateTime->greaterThan($exam->end) && $exam->status === 'Pending') {
                    $exam->status = 'Missing';
                }

                return $exam;
            });

        // Calculate totals and counts
        $totalExams = \DB::table('tblschedule')
            ->where('classtable_id', $classtable_id)
            ->where('tblschedule.status', 1)
            ->count(); // Total number of all exams (published or not)

        $totalMissing = $exams->where('status', 'Missing')->count();
        $totalPending = $exams->where('status', 'Pending')->count();
        $totalScore = $exams->filter(function ($exam) {
            return $exam->total_score !== 'Unfinished';
        })->sum('total_score');
        $numberOfFinishedExams = $exams->where('total_score', '!=', 'Unfinished')->count();

        // Check if no exams are found
        if ($exams->isEmpty()) {
            return response()->json(['message' => 'No published exams found for this class'], 404);
        }

        // Process the exams to include total points and total questions
        $examsWithDetails = $exams->map(function ($exam) {
            // Get total points from the questions
            $totalPoints = \DB::table('tblquestion')
                ->join('correctanswer', 'tblquestion.id', '=', 'correctanswer.tblquestion_id')
                ->where('tblquestion.tblschedule_id', $exam->id)
                ->sum('correctanswer.points');  // Assuming there is a `points` column for the correct answer

            // Get total number of questions
            $totalQuestions = \DB::table('tblquestion')
                ->where('tblquestion.tblschedule_id', $exam->id)
                ->count();

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'quarter' => $exam->quarter,
                'start' => $exam->start,
                'end' => $exam->end,
                'total_score' => $exam->total_score,
                'status' => $exam->status,
                'total_points' => $totalPoints,
                'total_questions' => $totalQuestions
            ];
        });

        return response()->json([
            'exams' => $examsWithDetails,
            'totals' => [
                'total_exams' => $totalExams,
                'total_missing' => $totalMissing,
                'total_pending' => $totalPending,
                'number_of_finished_exams' => $numberOfFinishedExams
            ]
        ], 200);

    } catch (\Exception $e) {
        Log::error('Failed to retrieve exams: ' . $e->getMessage());
        return response()->json(['error' => 'Internal server error. Please try again later.'], 500);
    }
}



public function viewAllExamsInAllClasses()
{
    try {
        // Get the authenticated user
        $user = auth()->user();

        // Ensure only students can view exams
        if (!$user || $user->usertype !== 'student') {
            return response()->json(['error' => 'Unauthorized: Only students can view exams.'], 403);
        }

        // Fetch the student’s enrolled classroom IDs
        $classIds = DB::table('joinclass')
            ->where('user_id', $user->id)
            ->where('status', 1) // Status 1 means the student is approved
            ->pluck('class_id'); // Get all class IDs the student is enrolled in

        // Check if the student is enrolled in any classrooms
        if ($classIds->isEmpty()) {
            return response()->json(['message' => 'The student is not enrolled in any classrooms.'], 404);
        }

        // Count the total number of distinct classes the student is enrolled in
        $totalClasses = $classIds->count();

        // Retrieve exams from these classrooms
        $exams = \DB::table('tblschedule')
            ->leftJoin('tblresult', function ($join) use ($user) {
                $join->on('tblschedule.id', '=', 'tblresult.exam_id')
                     ->where('tblresult.users_id', '=', $user->id);  // Filter by authenticated user
            })
            ->whereIn('tblschedule.classtable_id', $classIds)
            ->where('tblschedule.status', 1) // Only published exams
            ->select(
                'tblschedule.id',
                'tblschedule.title',
                'tblschedule.quarter',
                'tblschedule.start',
                'tblschedule.end',
                'tblresult.total_score', // Include the student's result score
                'tblresult.status' // Include the result status
            )
            ->orderBy('tblschedule.created_at', 'desc')
            ->get()
            ->map(function ($exam) {
                $currentDateTime = now(); // Get the current date and time

                // Convert total_score to whole number or mark as "Unfinished" if null
                $exam->total_score = $exam->total_score === null ? 'Unfinished' : (int)round($exam->total_score);

                // Check if the exam's end time has passed
                if ($currentDateTime->greaterThan($exam->end)) {
                    $exam->status = 'Missing';
                } else {
                    // Convert status to meaningful label, including 'Pending' for null
                    $exam->status = $exam->status === null ? 'Pending' : ($exam->status == 1 ? 'Passed' : 'Failed');
                }

                return $exam;
            });

        // Calculate totals and counts
        $totalExams = \DB::table('tblschedule')
            ->whereIn('tblschedule.classtable_id', $classIds)
            ->where('tblschedule.status', 1) 
            ->count(); // Total number of all exams (published or not)

        $totalMissing = $exams->where('status', 'Missing')->count();
        $totalPending = $exams->where('status', 'Pending')->count();
        $totalScore = $exams->filter(function ($exam) {
            return $exam->total_score !== 'Unfinished';
        })->sum('total_score');
        $numberOfFinishedExams = $exams->where('total_score', '!=', 'Unfinished')->count();

        // Check if no exams are found
        if ($exams->isEmpty()) {
            return response()->json(['message' => 'No published exams found for the enrolled classrooms.'], 404);
        }

        // Process the exams to include total points and total questions
        $examsWithDetails = $exams->map(function ($exam) {
            // Get total points from the questions
            $totalPoints = \DB::table('tblquestion')
                ->join('correctanswer', 'tblquestion.id', '=', 'correctanswer.tblquestion_id')
                ->where('tblquestion.tblschedule_id', $exam->id)
                ->sum('correctanswer.points');  // Assuming there is a `points` column for the correct answer

            // Get total number of questions
            $totalQuestions = \DB::table('tblquestion')
                ->where('tblquestion.tblschedule_id', $exam->id)
                ->count();

            return [
               'id' => $exam->id,
                'title' => $exam->title,
                'quarter' => $exam->quarter,
                'start' => $exam->start,
                'end' => $exam->end,
                'total_score' => $exam->total_score,
                'status' => $exam->status,
                'total_points' => $totalPoints,
                'total_questions' => $totalQuestions
            ];
        });

        return response()->json([
            'exams' => $examsWithDetails,
            'totals' => [
                'total_exams' => $totalExams,
                'total_missing' => $totalMissing,
                'total_pending' => $totalPending,
              //  'total_score' => $totalScore,
                'number_of_finished_exams' => $numberOfFinishedExams,
                'total_classes' => $totalClasses // Include the total number of classes
            ]
        ], 200);

    } catch (\Exception $e) {
        Log::error('Failed to retrieve exams: ' . $e->getMessage());
        return response()->json(['error' => 'Internal server error. Please try again later.' . $e->getMessage()], 500);
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
                // Check if the question already exists in the exam for this instruction
                $existingQuestion = Question::where('tblschedule_id', $exam->id)
                    ->where('question', $qData['question'])
                    ->exists();

                if ($existingQuestion) {
                    // If the question already exists, skip adding it and log the occurrence
                    Log::info("Duplicate question found for exam ID {$exam->id}: " . $qData['question']);
                    continue;  // Skip this question to prevent duplicates
                }

                // Proceed with adding the new question
                $totalQuestions++;

                $question = Question::create([
                    'tblschedule_id' => $exam->id,
                    'question' => $qData['question'],
                ]);

                $choiceMap = [];

                // Add choices if they exist
                if (isset($qData['choices'])) {
                    foreach ($qData['choices'] as $index => $choice) {
                        $newChoice = Choice::create([
                            'tblquestion_id' => $question->id,
                            'choices' => $choice
                        ]);
                        $choiceMap[$index] = $newChoice->id;
                    }
                }

                // Add correct answers if they exist
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
        $totalPossiblePoints = $examSchedule->points_exam; // Use points_exam from the examSchedule

        // Retrieve the student's answers for the specific exam
        $results = AnsweredQuestion::where('users_id', $user->id)
            ->whereHas('tblquestion', function ($query) use ($examId) {
                $query->where('tblschedule_id', $examId);
            })
            ->with(['tblquestion', 'addchoices'])
            ->get();

        // Check if any results were found
        if ($results->isEmpty()) {
            Log::error('Failed to retrieve exam results: No results found.');
            return response()->json(['message' => 'No results found for this exam.'], 404);
        }

        // Retrieve all correct answers for the questions in the exam
        $correctAnswers = CorrectAnswer::whereIn('tblquestion_id', $results->pluck('tblquestion_id'))
            ->get()
            ->keyBy('tblquestion_id');

        // Calculate points per question and total score
        $questionScores = $results->map(function ($result) use ($correctAnswers) {
            $correctAnswer = $correctAnswers->get($result->tblquestion_id);
            $isCorrect = $correctAnswer && $result->Student_answer === $correctAnswer->correct_answer;
            $points = $isCorrect ? $correctAnswer->points : 0;

            return [
                'question' => $result->tblquestion->question,
                'student_answer' => $result->Student_answer,
                'correct_answer' => $correctAnswer ? $correctAnswer->correct_answer : null,
                'points_awarded' => $points,
                'total_possible_points' => $correctAnswer ? $correctAnswer->points : 0,
                'is_correct' => $isCorrect
            ];
        });

        // Calculate total score
        $totalScore = $questionScores->sum('points_awarded');
        $average = $totalPossiblePoints > 0 ? ($totalScore / $totalPossiblePoints) * 100 : 0; // Percentage

        // Calculate passing or failing status
        $passingThreshold = $totalPossiblePoints * 0.50; // 75% of total possible points
        $status = $totalScore >= $passingThreshold ? 1 : 0;

        // Save or update the result in tblresult
        DB::table('tblresult')->updateOrInsert(
            ['users_id' => $user->id, 'exam_id' => $examId],
            [
                'total_score' => $totalScore,
                'total_exam' => $totalPossiblePoints,
                'average' => $average,
                'status' => $status
            ]
        );

        return response()->json([
            'results' => $questionScores,
            'total_score' => $totalScore,
            'total_possible_points' => $totalPossiblePoints,
            'status' => $status
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




public function viewExam2updated2($exam_id)
{
    $user = auth()->user();

    // Ensure only students can view exams
    if ($user->usertype !== 'student') {
        return response()->json(['error' => 'Unauthorized: Only students can view exams.'], 403);
    }

    try {
        // Retrieve the exam with instructions and questions
        $exam = Exam::with(['instructions.choices'])
            ->where('id', $exam_id)
            ->where('status', 1) // Ensure the exam is published
            ->firstOrFail();

        $totalItems = $exam->total_items; // Get the limit of questions for the exam
        $pointsLimit = $exam->points_exam; // Points limit for the exam

        // Initialize variables for tracking points and questions
        $totalPoints = 0;
        $selectedQuestions = collect();

        // Get limited questions based on total_items
        $questions = $exam->instructions->questions()->limit($totalItems)->get();

        foreach ($questions as $question) {
            // Assuming 'points' is a field in the correct answer or calculated in some way
            $questionPoints = $question->correctAnswer->points ?? 0; // Handle case where correct answer might not exist

            // Check if adding this question exceeds the points_exam limit
            if ($totalPoints + $questionPoints > $pointsLimit) {
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

        // Attach the shuffled questions back to the exam object
        $exam->instructions->questions = $shuffledQuestions;

        return response()->json([
            'exam' => $exam,
            'total_items' => $shuffledQuestions->count(), // Show the number of questions being returned
            'total_points' => $totalPoints
        ], 200);
    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam details: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exam details. Please try again later.'], 500);
    }
}


























public function getAllExamsByClass($classId)
{
    try {
        // Fetch exams filtered by the class ID
        $exams = Exam::where('classtable_id', $classId)
        ->orderBy('tblschedule.created_at', 'desc')
        ->get();

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



public function getExamInstructionAndCorrectAnswers($exam_id)
{
    try {
       // $exam = Exam::findOrFail($examId);

       $instructions = Exam::with(['instructions.questions.choices', 'instructions.questions.correctAnswers'])
       ->where('id', $exam_id)
       ->where('status', 1) // Check if the exam is published
       ->get();

       // $instructions = Instructions::where('schedule_id', $examId)->with(['questions.choices.correctAnswers'])->get();

        return response()->json([
         //   'exam' => $exam,
            'instructions' => $instructions,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam questions: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exam questions.'. $e->getMessage()], 500);
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

        // Collect existing question IDs to determine which ones to delete
        $existingQuestionIds = Question::where('tblschedule_id', $examId)->pluck('id')->toArray();
        $newQuestionIds = [];

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
                $newQuestionIds[] = $question->id; // Keep track of updated question IDs
            } else {
                // Create new question
                $question = Question::create([
                    'tblschedule_id' => $exam->id,
                    'question_type' => $qData['question_type'],
                    'question' => $qData['question']
                ]);
                $newQuestionIds[] = $question->id; // Keep track of new question IDs
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

        // Delete questions that were not included in the update request
        $questionsToDelete = array_diff($existingQuestionIds, $newQuestionIds);
        if ($questionsToDelete) {
            Question::destroy($questionsToDelete);
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



public function storetestbank(Request $request)
{
    $validatedData = $request->validate([
        'schedule_id' => 'required|exists:tblschedule,id', // Get schedule ID from request
        'questions' => 'required|array',
        'questions.*.question_id' => 'required|exists:tblquestion,id',
        'questions.*.correct_id' => 'required|exists:correctanswer,id',
        'questions.*.choices' => 'required|array',
        'questions.*.choices.*.choice_id' => 'nullable|exists:addchoices,id', // choice_id is now nullable
    ]);

    // Get the authenticated user's ID
    $userId = auth()->id();

    // Find the tblschedule by its ID
    $tblschedule = Exam::findOrFail($validatedData['schedule_id']);

    // Get the quarter from tblschedule
    $quarter = $tblschedule->quarter;

    // Find the related tblclasstable by its ID in tblschedule
    $classtableId = tblclass::findOrFail($tblschedule->classtable_id);

    // Extract subject_id from tblclasstable
    $subjectId = $classtableId->subject_id;

    // Loop through the questions array and create TblBank entries
    foreach ($validatedData['questions'] as $question) {
        foreach ($question['choices'] as $choice) {
            // Check if the record already exists
            $existingRecord = tblbank::where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->where('question_id', $question['question_id'])
                ->where('choice_id', $choice['choice_id'] ?? null)
                ->where('correct_id', $question['correct_id'])
                ->where('Quarter', $quarter)
                ->exists();

            // Create the record only if it does not already exist
            if (!$existingRecord) {
                tblbank::create([
                    'user_id' => $userId, // Use authenticated user's ID
                    'subject_id' => $subjectId, // Insert subject_id from tblclasstable
                    'question_id' => $question['question_id'],
                    'choice_id' => $choice['choice_id'] ?? null, // choice_id can be null
                    'correct_id' => $question['correct_id'], // Correct ID is now associated with the question
                    'Quarter' => $quarter, // Get quarter from tblschedule
                ]);
            }
        }
    }

    return response()->json(['message' => 'Records created successfully'], 201);
}




public function viewTestBank(Request $request)
{
    // Validate that schedule_id is present in the request
    $request->validate([
        'schedule_id' => 'required|exists:tblschedule,id'
    ]);

    // Get the authenticated user's ID
    $userId = auth()->id(); // Automatically get the authenticated user's ID

    // Retrieve schedule_id directly from the validated request
    $scheduleId = $request->validated()['schedule_id'];

    // Find the tblschedule by its ID
    $tblschedule = Exam::findOrFail($scheduleId);

    // Get the quarter from tblschedule
    $quarter = $tblschedule->quarter;

    // Find the related tblclasstable by its ID in tblschedule
    $classtable = tblclass::findOrFail($tblschedule->classtable_id);

    // Extract subject_id from tblclasstable
    $subjectId = $classtable->subject_id;

    // Optional filter for quarter
    $filterQuarter = $request->query('quarter', $quarter); // Default to quarter from tblschedule if not provided

    // Build the query
    $query = tblbank::with(['question', 'choices', 'correct_answer']) // Use Eloquent relationships for eager loading
                ->where('user_id', $userId) // Filter by authenticated user's ID
                ->where('subject_id', $subjectId); // Filter by derived subject_id

    // Apply optional filter for quarter if provided
    if ($filterQuarter) {
        $query->where('Quarter', $filterQuarter);
    }

    // Get all results (without pagination)
    $testBankRecords = $query->get();

    // Return the result as JSON
    return response()->json($testBankRecords);
}

//pag view ng exam sa student side
public function getResultsallexam(Request $request)
{
    try {
        // Get the authenticated user
        $user = Auth::user();

        // Validate that classtable_id is provided in the request
        $request->validate([
            'classtable_id' => 'required|integer'
        ]);

        // Retrieve results specific to the authenticated user and the class
        $results = DB::table('tblresult')
            ->join('users', 'tblresult.users_id', '=', 'users.id')
            ->join('tblschedule', 'tblresult.exam_id', '=', 'tblschedule.id')
            ->select(
                'tblresult.id',
                'users.lname AS student_name',
                'tblschedule.title AS exam_title',
                'tblschedule.start',
                'tblschedule.end',
                'tblschedule.classtable_id',  // Add classtable_id
                'tblresult.total_score',
                'tblresult.average',
                'tblresult.total_exam',
                'tblresult.status',
                'tblresult.created_at',
                'tblresult.updated_at'
            )
            ->where('tblresult.users_id', $user->id)  // Filter by authenticated user
            ->where('tblschedule.classtable_id', $request->classtable_id)  // Filter by classtable_id from the request
            ->get()
            ->map(function ($result) {
                // Transform status code to meaningful labels
                $result->status = $result->status == 1 ? 'Passed' : 'Failed';
                return $result;
            });

        // Check if results are empty
        if ($results->isEmpty()) {
            return response()->json(['message' => 'No exam results found for this user in this class.'], 404);
        }

        return response()->json($results, 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to retrieve exam results. Please try again later.',
            'exception' => $e->getMessage()
        ], 500);
    }
}

//for teacher
public function getAllStudentResults(Request $request)
{
    try {
        // Get the authenticated teacher
        $teacher = Auth::user();

        // Validate that classtable_id is provided in the request
        $request->validate([
            'classtable_id' => 'required|integer'
        ]);

        // Retrieve all student results for the specified class
        $results = DB::table('users')
            ->leftJoin('joinclass', 'users.id', '=', 'joinclass.user_id') // Join with joinclass to get enrolled students
            ->leftJoin('tblschedule', function ($join) use ($request) {
                $join->on('tblschedule.classtable_id', '=', 'joinclass.class_id')
                     ->where('tblschedule.classtable_id', $request->classtable_id); // Filter by class
            })
            ->leftJoin('tblresult', function ($join) {
                $join->on('tblresult.users_id', '=', 'users.id')
                     ->on('tblresult.exam_id', '=', 'tblschedule.id'); // Match results with students and exams
            })
            ->select(
                'users.id AS student_id',
                'users.idnumber AS Lrn_id',
                'users.lname AS Last_name',
                'users.fname AS First_name',
                'users.mname AS Middle_i',
                'tblschedule.title AS exam_title',
                'tblschedule.start',
                'tblschedule.end',
                'tblresult.total_score',
                'tblresult.average',
                'tblresult.total_exam',
                'tblresult.status',
                'tblresult.created_at',
                'tblresult.updated_at'
            )
            ->where('joinclass.class_id', $request->classtable_id)  // Filter by class
            ->where('tblschedule.classtable_id', $request->classtable_id) // Ensure the schedule is in the correct class
            ->where('joinclass.status', 1)  // Ensure the student is actively joined
            ->where('tblschedule.id', '!=', null)  // Ensure schedule ID exists
            ->orderBy('users.lname')  // Sort by student last name (lname) alphabetically
            ->orderBy('users.fname')  // Then sort by student first name (fname) alphabetically
            ->get()
            ->map(function ($result) {
                // Transform status code to meaningful labels
                $result->status = $result->status === null ? 'N/A' : ($result->status == 1 ? 'Passed' : 'Failed');
                return $result;
            });

        // Check if results are empty
        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'No exam results found for this class.',
                'results' => $results
            ], 404);
        }

        // Calculate total finished and unfinished students per exam
        $resultsByExam = $results->groupBy('exam_title')->map(function ($examResults) {
            $finishedStudentsCount = $examResults->where('status', 'Passed','Failed')->count();
            $unfinishedStudentsCount = $examResults->where('status', 'N/A')->count();
            $allScores = $examResults->map(function ($result) {
                return [
                    'student_id' => $result->student_id,
                    'Lrn_id' => $result->Lrn_id,
                    'Last_name' => $result->Last_name,
                    'First_name' => $result->First_name,
                    'Middle_i' => $result->Middle_i,
                    'total_score' => $result->total_score,
                    'total_exam' => $result->total_exam,
                    'exam_start' => $result->start,
                    'exam_end' => $result->end,
                    'status' => $result->status
                ];
            });

            return [
                'exam_results' => $allScores,
                'finished_students' => $finishedStudentsCount,
                'unfinished_students' => $unfinishedStudentsCount
            ];
        });

        return response()->json([
            'results' => $resultsByExam
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to retrieve exam results. Please try again later.',
            'exception' => $e->getMessage()
        ], 500);
    }
}




public function itemAnalysis(Request $request)
{
    // Validate the request
    $request->validate([
        'examId' => 'required|integer',
    ]);

    $examId = $request->input('examId');

    // Fetch the exam details and automatically get the class ID
    $examSchedule = Exam::where('id', $examId)->firstOrFail();
    $classId = $examSchedule->classtable_id; // Automatically get the classId from the exam

    // Retrieve all students in the class
    $students = joinclass::where('class_id', $classId)
        ->where('status', 1) // Only get active students
        ->with('user') // Load the related user model
        ->get();

    // Retrieve all questions for the exam
    $questions = Question::where('tblschedule_id', $examId)
        ->with('addchoices') // Load choices for each question
        ->get();

    // Initialize an array to hold item analysis data
    $itemAnalysis = [];

    foreach ($questions as $question) {
        $questionId = $question->id;

        // Retrieve all choices for the question
        $choices = $question->addchoices;

        // Initialize counts
        $choiceCounts = $choices->mapWithKeys(function ($choice) {
            return [$choice->id => 0];
        })->toArray();

        // Count the number of students who chose each choice
        $studentsWhoAnswered = AnsweredQuestion::where('tblquestion_id', $questionId)
            ->whereHas('tblquestion', function ($query) use ($examId) {
                $query->where('tblschedule_id', $examId); // Ensure `tblschedule_id` is used from `tblquestion` model
            })
            ->get();

        foreach ($studentsWhoAnswered as $answeredQuestion) {
            if (isset($choiceCounts[$answeredQuestion->addchoices_id])) {
                $choiceCounts[$answeredQuestion->addchoices_id]++;
            }
        }

        // Get the correct answer for the question
        $correctAnswer = CorrectAnswer::where('tblquestion_id', $questionId)->first();
        $correctAnswerText = $correctAnswer && $correctAnswer->addchoices ? $correctAnswer->addchoices->choices : $correctAnswer->correct_answer;

        // Count the number of correct answers
        $correctAnswersCount = AnsweredQuestion::where('tblquestion_id', $questionId)
            ->whereHas('addchoices', function ($query) use ($correctAnswer) {
                $query->where('id', $correctAnswer->addchoices_id ?? 0);
            })
            ->count();

        // Calculate percentages and format them with %
        $totalAnswered = count($studentsWhoAnswered);
        $choicesWithPercentage = $choices->map(function ($choice) use ($choiceCounts, $totalAnswered) {
            $count = $choiceCounts[$choice->id] ?? 0;
            $percentage = $totalAnswered > 0 ? ($count / $totalAnswered) * 100 : 0;
            return [
                'choice' => $choice->choices,
                'count' => $count,
                'percentage' => round($percentage, 2) . '%' // Append % sign and round to 2 decimal places
            ];
        });

        // Calculate difficulty percentage
        $difficultyPercentage = $totalAnswered > 0 ? ($correctAnswersCount / $totalAnswered) * 100 : 0;

        $itemAnalysis[] = [
            'question_id' => $questionId,
            'question' => $question->question,
            'choices' => $choicesWithPercentage,
            'correct_answer' => $correctAnswerText,
            'difficulty_percentage' => round($difficultyPercentage, 2) . '%' // Add difficulty percentage to the response
        ];
    }

    return response()->json([
        'item_analysis' => $itemAnalysis,
        'total_students' => $students->count()
    ], 200);
}








public function getResultsallexam2(Request $request)
{
    try {
        // Get the authenticated user
        $user = Auth::user();

        // Validate that classtable_id is provided in the request
        $request->validate([
            'classtable_id' => 'required|integer'
        ]);

        // Retrieve results specific to the authenticated user and the class
        $results = DB::table('tblresult')
            ->join('users', 'tblresult.users_id', '=', 'users.id')
            ->join('tblschedule', 'tblresult.exam_id', '=', 'tblschedule.id')
            ->select(
                'tblresult.id',
                'users.lname AS student_name',
                'tblschedule.title AS exam_title',
                'tblschedule.start',
                'tblschedule.end',
                'tblschedule.classtable_id',
                'tblresult.total_score',
                'tblresult.average',
                'tblresult.total_exam',
                'tblresult.status',
                'tblresult.created_at',
                'tblresult.updated_at'
            )
            ->where('tblresult.users_id', $user->id)
            ->where('tblschedule.classtable_id', $request->classtable_id)
            ->get()
            ->map(function ($result) {
                $result->status = $result->status == 1 ? 'Passed' : 'Failed';
                $result->exam_status = $result->status == 'Passed' ? 'Finished' : 'Unfinished';
                return $result;
            });

        // Check if results are empty
        if ($results->isEmpty()) {
            return response()->json(['message' => 'No exam results found for this user in this class.'], 404);
        }

        // Count finished and unfinished exams
        $totalExams = $results->count();
        $finishedExams = $results->where('exam_status', 'Finished')->count();
        $unfinishedExams = $results->where('exam_status', 'Unfinished')->count();

        // Return results with counts
        return response()->json([
            'total_exams' => $totalExams,
            'finished_exams_count' => $finishedExams,
            'unfinished_exams_count' => $unfinishedExams,
            'results' => $results,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to retrieve exam results. Please try again later.',
            'exception' => $e->getMessage()
        ], 500);
    }
}


public function analysisStudent(Request $request, $examId)
{
    // Validate the request
    $request->validate([
        'user_id' => 'required|integer|exists:users,id', // Validate user_id is provided and exists in the users table
    ]);

    $userId = $request->input('user_id'); // Get the user ID from the request

    try {
        // Fetch the exam details
        $examSchedule = Exam::where('id', $examId)->firstOrFail();
        $totalPossiblePoints = $examSchedule->points_exam; // Use points_exam from the examSchedule

        // Retrieve the student's answers for the specific exam
        $results = AnsweredQuestion::where('users_id', $userId)
            ->whereHas('tblquestion', function ($query) use ($examId) {
                $query->where('tblschedule_id', $examId); // Ensure this column exists in tblquestion table
            })
            ->with(['tblquestion.addchoices', 'addchoices']) // Retrieve all choices for each question and student's selected choice
            ->get();

        // Check if any results were found
        if ($results->isEmpty()) {
            Log::error('Failed to retrieve exam results: No results found.');
            return response()->json(['message' => 'No results found for this exam.'], 404);
        }

        // Retrieve all correct answers for the questions in the exam
        $correctAnswers = CorrectAnswer::whereIn('tblquestion_id', $results->pluck('tblquestion_id'))
            ->with('addchoices') // Load correct answer choices
            ->get()
            ->keyBy('tblquestion_id');

        // Calculate points per question and total score
        $questionScores = $results->map(function ($result) use ($correctAnswers) {
            $correctAnswer = $correctAnswers->get($result->tblquestion_id);

            // Compare the student's selected answer (addchoices_id) with the correct answer (addchoices_id)
            $isCorrect = $correctAnswer && ($result->addchoices_id === $correctAnswer->addchoices_id || $result->addchoices->choices === $correctAnswer->correct_answer);
            $points = $isCorrect ? $correctAnswer->points : 0;

            // Get all choices for the question
            $allChoices = $result->tblquestion->addchoices->map(function ($choice) {
                return $choice->choices;
            });

            return [
                'question' => $result->tblquestion->question,
                'all_choices' => $allChoices, // All possible choices for the question
                'student_answer' => $result->addchoices ? $result->addchoices->choices : null, // Show student's selected answer
                'correct_answer' => $correctAnswer && $correctAnswer->addchoices ? $correctAnswer->addchoices->choices : $correctAnswer->correct_answer, // Show correct answer
                'points_awarded' => $points,
                'total_possible_points' => $correctAnswer ? $correctAnswer->points : 0,
                'is_correct' => $isCorrect
            ];
        });

        // Calculate total score
        $totalScore = $questionScores->sum('points_awarded');
        $average = $totalPossiblePoints > 0 ? ($totalScore / $totalPossiblePoints) * 100 : 0; // Percentage

        // Calculate passing or failing status
        $passingThreshold = $totalPossiblePoints * 0.75; // 75% of total possible points
        $status = $totalScore >= $passingThreshold ? 1 : 0;
        $statusText = $status ? 'Passed' : 'Failed'; // Human-readable status

        // Save or update the result in tblresult
        DB::table('tblresult')->updateOrInsert(
            ['users_id' => $userId, 'exam_id' => $examId],
            [
                'total_score' => $totalScore,
                'total_exam' => $totalPossiblePoints,
                'average' => $average,
                'status' => $status
            ]
        );

        return response()->json([
            'results' => $questionScores,
            'total_score' => $totalScore,
            'total_possible_points' => $totalPossiblePoints,
            'status' => $status,
            'status_text' => $statusText 
        ], 200);
    } catch (\Exception $e) {
        Log::error('Failed to retrieve exam results: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to retrieve exam results. Please try again later. ' . $e->getMessage()], 500);
    }
}

public function createAndPublishExam(Request $request)
{
    // Validate the request data
    $request->validate([
        'classtable_id' => 'required|exists:tblclass,id',
        'title' => 'required|string',
        'quarter' => 'required|string',
        'start' => 'required|date_format:Y-m-d H:i:s',
        'end' => 'required|date_format:Y-m-d H:i:s',
        'points_exam' => 'required|numeric',
        'name' => 'required|string'  // Validate the 'name' field as well
    ]);

    try {
        $name = $request->input('name');

        // Create the exam and set status to 0 (not published yet)
        $exam = Exam::create([
            'classtable_id' => $request->input('classtable_id'),
            'title' => $request->input('title'),
            'quarter' => $request->input('quarter'),
            'start' => $request->input('start'),
            'end' => $request->input('end'),
            'points_exam' => $request->input('points_exam'),
            'status' => 0 // Initially not published
        ]);

        // Retrieve class_id from the newly created exam
        $class_id = $exam->classtable_id;

        // Get students enrolled in the class
        $students = DB::table('users')
            ->join('joinclass', 'users.id', '=', 'joinclass.user_id')
            ->where('joinclass.class_id', $class_id)
            ->where('joinclass.status', 1)  // Ensure only active students
            ->where('users.usertype', 'student')
            ->select('users.email')  // Select only the email
            ->get();

        // Notify students that the exam is created but not yet published
        foreach ($students as $student) {
            Mail::to($student->email)->send(new WelcomeMail($name));
        }

        // Check if the current date/time is greater than or equal to the start date
        $currentDateTime = Carbon::now();
        $examStartDateTime = Carbon::parse($exam->start);

        if ($currentDateTime->greaterThanOrEqualTo($examStartDateTime)) {
            // If the current time is greater than or equal to the start time, publish the exam
            $exam->status = 1;
            $exam->save();

            // Return success response
            return response()->json([
                'message' => 'Exam created, published, and emails sent successfully',
                'exam' => $exam
            ], 201);
        }

        // Return response indicating that the exam has been created but not yet published
        return response()->json([
            'message' => 'Exam created successfully but not yet published. Students have been notified.',
            'exam' => $exam
        ], 201);

    } catch (\Exception $e) {
        // Log the error for debugging
        Log::error('Failed to create and publish exam: ' . $e->getMessage());

        // Return a 500 Internal Server Error
        return response()->json(['error' => 'Failed to create and publish exam.'], 500);
    }
}


}