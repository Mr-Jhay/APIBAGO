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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Fpdf\Fpdf;

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
                'tblschedule.points_exam',
                'tblresult.total_score', // Include the student's result score
                'tblresult.status', // Include the result status from tblresult
                'tblresult.average',
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
                    $exam->status == 1 ? 'Passed' : 'Failed';
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
                'points_exam' => $exam->points_exam,
                'status' => $exam->status,
                'total_points' => $totalPoints,
                'total_questions' => $totalQuestions,
                'average' => $exam->average,
              
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
        return response()->json(['error' => 'Internal server error. Please try again later.'. $e->getMessage()], 500);
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

          // Get time consumed from request (in minutes or seconds)
          $timeConsumed = $request->input('time_consumed', null); // Ensure this value is provided from the frontend

        // Save or update the result in tblresult
        DB::table('tblresult')->updateOrInsert(
            ['users_id' => $user->id, 'exam_id' => $examId],
            [
                'total_score' => $totalScore,
                'total_exam' => $totalPossiblePoints,
                'average' => $average,
                'status' => $status,
                'time_consumed' => $timeConsumed  // Store time consumed
            ]
        );

        return response()->json([
            'results' => $questionScores,
            'total_score' => $totalScore,
            'total_possible_points' => $totalPossiblePoints,
            'average' => $average,
            'status' => $status,
            'time_consumed' => $timeConsumed // Return time consumed
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

        // Shuffle the choices directly from the Choices table for each question
        $shuffledQuestions->transform(function ($question) {
            // Retrieve choices from the database
            $choices = Choice::where('tblquestion_id', $question->id) // Assuming there's a question_id column
                ->get()
                ->shuffle(); // Shuffle the collection of choices

            // Assign the shuffled choices back to the question
            $question->choices = $choices;

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
        return response()->json(['error' => 'Failed to retrieve exam details. Please try again later.' .$e->getMessage()], 500);
    }
}


























public function getAllExamsByClass($classId)
{
    try {
        // Fetch exams filtered by the class ID
        $exams = Exam::where('classtable_id', $classId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Check if any exams were found
        if ($exams->isEmpty()) {
            return response()->json([
                'message' => 'No exams found for this class.'
            ], 404);
        }

        // Retrieve all students in the class
        $students = joinclass::where('class_id', $classId)
            ->where('status', 1) // Only get active students
            ->get();
        $totalStudents = $students->count(); // Total number of students in the class

        $examsWithCompletion = $exams->map(function ($exam) use ($totalStudents) {
            // Retrieve students who completed the exam
            $studentsWhoCompleted = AnsweredQuestion::whereHas('tblquestion', function ($query) use ($exam) {
                $query->where('tblschedule_id', $exam->id);
            })
            ->groupBy('users_id')
            ->pluck('users_id');

            $studentsCompletedCount = $studentsWhoCompleted->count(); // Number of students who completed the exam

            // Calculate the completion percentage
            $completionPercentage = $totalStudents > 0 ? ($studentsCompletedCount / $totalStudents) * 100 : 0;

            return [
                'exam_id' => $exam->id,
                'title' => $exam->title,
                'quarter' => $exam->quarter,
                'start' => $exam->start,
                'end' => $exam->end,
                'points_exam' => $exam->points_exam,
                'students_completed' => $studentsCompletedCount,
                'total_students' => $totalStudents,
                'completion_percentage' => round($completionPercentage, 2) . '%', // Add completion percentage to the response
            ];
        });

        return response()->json([
            'exams' => $examsWithCompletion
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
    // Validate the request data
    $request->validate([
        'title' => 'nullable|string',
        'quarter' => 'nullable|string',
        'start' => 'nullable|date_format:Y-m-d H:i:s',
        'end' => 'nullable|date_format:Y-m-d H:i:s',
        'points_exam' => 'nullable|numeric',
        'instructions' => 'nullable|array',
        'instructions.*.instruction' => 'nullable|string',
        'instructions.*.question_type' => 'nullable|string',
        'instructions.*.questions' => 'nullable|array',
        'instructions.*.questions.*.question' => 'nullable|string',
        'instructions.*.questions.*.choices' => 'nullable|array',
        'instructions.*.questions.*.choices.*' => 'nullable|string',
        'instructions.*.questions.*.correct_answers' => 'nullable|array',
        'instructions.*.questions.*.correct_answers.*.correct_answer' => 'nullable|string',
        'instructions.*.questions.*.correct_answers.*.points' => 'nullable|integer',
    ]);

    try {
        DB::beginTransaction();

        // Fetch the existing exam
        $exam = Exam::findOrFail($examId);

        // Update the exam details with the nullable fields
        $exam->update($request->only([ 'title', 'quarter', 'start', 'end', 'points_exam']));

        $totalPoints = 0;
        $totalQuestions = 0;
        $groupedQuestions = [];

        // Loop through each instruction and its questions if provided
        if ($request->has('instructions')) {
            foreach ($request->input('instructions') as $instructionData) {
                // Update or create instructions
                $instruction = Instruction::updateOrCreate(
                    [
                        'schedule_id' => $examId,
                        'instruction' => $instructionData['instruction'] ?? null,
                        'question_type' => $instructionData['question_type'] ?? null
                    ],
                    $instructionData
                );

                $groupedQuestions[$instructionData['question_type'] ?? ''] = [];

                if (isset($instructionData['questions'])) {
                    foreach ($instructionData['questions'] as $qData) {
                        // Update or create the question
                        $question = Question::updateOrCreate(
                            [
                                'tblschedule_id' => $exam->id,
                                'question' => $qData['question'] ?? null,
                            ],
                            [
                                'question_type' => $instructionData['question_type'] ?? null,
                            ]
                        );

                        $choiceMap = [];

                        // Update or create choices if provided
                        if (isset($qData['choices'])) {
                            foreach ($qData['choices'] as $index => $choice) {
                                $newChoice = Choice::updateOrCreate(
                                    [
                                        'tblquestion_id' => $question->id,
                                        'choices' => $choice,
                                    ],
                                    [
                                        'choices' => $choice,
                                    ]
                                );
                                $choiceMap[$index] = $newChoice->id;
                            }
                        }

                        // Update or create correct answers if provided
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

                                // Update or create the correct answer
                                CorrectAnswer::updateOrCreate(
                                    [
                                        'tblquestion_id' => $question->id,
                                        'addchoices_id' => $correctAnswerChoiceId,
                                    ],
                                    [
                                        'correct_answer' => $ansData['correct_answer'] ?? null,
                                        'points' => $points,
                                    ]
                                );
                            }
                        }

                        $groupedQuestions[$instructionData['question_type'] ?? ''][] = $question;
                        $totalQuestions++;
                    }
                }
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Exam, instructions, and questions updated successfully',
            'total_points' => $totalPoints,
            'total_questions' => $totalQuestions,
            'grouped_questions' => $groupedQuestions
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to update exam: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to update exam.'], 500);
    }
}



public function deleteMultipleQuestions(Request $request, $examId)
{
    // Validate that question_ids is an array and contains valid IDs
    $request->validate([
        'question_ids' => 'required|array',
        'question_ids.*' => 'exists:tblquestion,id'
    ]);

    try {
        // Find the exam
        $exam = Exam::findOrFail($examId);

        // Begin a transaction to ensure all deletions happen together
        DB::beginTransaction();

        // Loop through all provided question IDs
        foreach ($request->input('question_ids') as $questionId) {
            // Find each question
            $question = Question::where('tblschedule_id', $exam->id)
                                ->where('id', $questionId)
                                ->firstOrFail();

            // Delete associated choices
            Choice::where('tblquestion_id', $question->id)->delete();

            // Delete associated correct answers
            CorrectAnswer::where('tblquestion_id', $question->id)->delete();

            // Delete the question itself
            $question->delete();
        }

        // Commit the transaction if everything is successful
        DB::commit();

        return response()->json([
            'message' => 'Questions deleted successfully.'
        ], 200);

    } catch (\Exception $e) {
        // Roll back the transaction in case of an error
        DB::rollBack();
        Log::error('Failed to delete questions: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to delete questions.'], 500);
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
                'tblschedule.quarter',
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
       // ->Join('tblstrand', 'tblstudent.strand_id', '=', 'tblstrand.id')
       // ->Join('tblstudent', 'users.strand_id', '=', 'tblstudent.id')
       ->leftJoin('tblstudent', 'users.id', '=', 'tblstudent.user_id') // Join users with tblstudent
       ->leftJoin('tblstrand', 'tblstudent.strand_id', '=', 'tblstrand.id')
       ->join('tblsection', 'tblstudent.section_id', '=', 'tblsection.id')
            ->select(
                'users.id AS student_id',
                'users.idnumber AS Lrn_id',
                'users.lname AS Last_name',
                'users.fname AS First_name',
                'users.mname AS Middle_i',
                'users.sex AS sex',
                'tblstrand.addstrand AS strand_name',
                'tblstrand.grade_level AS gradelevel_name',
                'tblsection.section',
                'tblschedule.title AS exam_title',
                'tblschedule.start',
                'tblschedule.end',
                'tblschedule.points_exam',
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
            ->get();

        // Check if results are empty
        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'No exam results found for this class.',
                'results' => $results
            ], 404);
        }

        // Prepare data structure for results by exam
        $resultsByExam = [];

        foreach ($results as $result) {
            $examTitle = $result->exam_title;

            // Initialize the exam if not set
            if (!isset($resultsByExam[$examTitle])) {
                $resultsByExam[$examTitle] = [
                    'exam_results' => [],
                    'finished_students' => 0,
                    'unfinished_students' => 0,
                    'scores' => []
                ];
            }

            // Status transformation
            $status = ($result->status === null) ? 'N/A' : (($result->status == 1) ? 'Passed' : 'Failed');
            $ps = ($result->points_exam > 0) ? number_format(($result->total_score / $result->points_exam) * 100, 2) : null;
            $ws = (    $ps !== null) ? number_format($ps * 0.20) : null;
            // Add each result to the exam_results array
            $resultsByExam[$examTitle]['exam_results'][] = [
                'student_id' => $result->student_id,
                'Lrn_id' => $result->Lrn_id,
                'Last_name' => $result->Last_name,
                'First_name' => $result->First_name,
                'Middle_i' => $result->Middle_i,
                'sex' => $result->sex,
                'strand_name' => $result->strand_name,
                'gradelevel_name' => $result->gradelevel_name,
                'section' => $result->section,
                'points_exam' => $result->points_exam,
                'ps' => $ps, // Add percentage score
                'ws' => $ws . '%',
                'total_score' => $result->total_score,
                'total_exam' => $result->total_exam,
                'exam_start' => $result->start,
                'exam_end' => $result->end,
                'status' => $status
            ];

            // Count finished and unfinished students
            if ($status === 'N/A') {
                $resultsByExam[$examTitle]['unfinished_students']++;
            } else {
                $resultsByExam[$examTitle]['finished_students']++;
            }
            $resultsByExam[$examTitle]['points_exam'] = $result->points_exam;  ///////// inilabas ko lang
            // Collect scores for statistical analysis
            if ($result->total_score !== null) {
                $resultsByExam[$examTitle]['scores'][] = $result->total_score;

                if (!isset($studentTotalScores[$result->student_id])) {
                    $studentTotalScores[$result->student_id] = [
                        'student_id' => $result->student_id,
                        'Lrn_id' => $result->Lrn_id,
                        'Last_name' => $result->Last_name,
                        'First_name' => $result->First_name,
                        'Middle_i' => $result->Middle_i,
                        'total_score' => 0
                    ];
                }
                $studentTotalScores[$result->student_id]['total_score'] += $result->total_score;
                
            }
        }
        $totalPointsExam = 0; /////////// total for overall computation
        

        foreach ($resultsByExam as $examTitle => &$examData) {
            // Ensure points_exam exists before using
            if (isset($examData['points_exam'])) {
                // Add the points_exam to the total
                $totalPointsExam += $examData['points_exam'];

                // Include the total points_exam below the points_exam for this exam
                $examData['total_points_exam'] = $totalPointsExam;
            }
}

        // Calculate statistics (mean, median, mode, range) for each exam
        foreach ($resultsByExam as $examTitle => &$examData) {
            $scores = $examData['scores'];
            $scoreCount = count($scores);

            if ($scoreCount > 0) {
                // Mean
                $mean = number_format(array_sum($scores) / $scoreCount, 2);

                // Median
                sort($scores);
                if ($scoreCount % 2 == 0) {
                    $median = ($scores[$scoreCount / 2 - 1] + $scores[$scoreCount / 2]) / 2;
                } else {
                    $median = $scores[floor($scoreCount / 2)];
                }

                // Mode
                $scoreCounts = array_count_values($scores);
                $mode = array_search(max($scoreCounts), $scoreCounts);

                // Range
                $range = max($scores) - min($scores);
            } else {
                $mean = null;
                $median = null;
                $mode = null;
                $range = null;
            }

            // Add calculated stats to the results
            $examData['mean_score'] = $mean;
            $examData['median_score'] = $median;
            $examData['mode_score'] = $mode;
            $examData['score_range'] = $range;
        }

        return response()->json([
            'results' => $resultsByExam,
            'student_total_scores' => array_values($studentTotalScores), 
            'total_points_exam_across_all_exams' => $totalPointsExam 
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

    $instruction = Instructions::where('schedule_id', $examId)->first();
    $instructionText = $instruction ? $instruction->instruction : 'No instructions provided.';

    $subjectTitle = $examSchedule->title ? $examSchedule->title : 'No subject title available';

    // Retrieve all students in the class
    $students = JoinClass::where('class_id', $classId)
        ->where('status', 1) // Only get active students
        ->with('user') // Load the related user model
        ->get();

    $totalStudents = $students->count(); // Total number of students in the class

    // Retrieve all students who answered at least one question in the exam
    $studentsWhoCompleted = AnsweredQuestion::whereHas('tblquestion', function ($query) use ($examId) {
        $query->where('tblschedule_id', $examId);
    })
    ->groupBy('users_id')
    ->pluck('users_id');

    $studentsCompletedCount = $studentsWhoCompleted->count(); // Number of students who took the exam

    // Calculate the percentage of students who completed the exam
    $completionPercentage = $totalStudents > 0 ? ($studentsCompletedCount / $totalStudents) * 100 : 0;

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

        // Initialize counts and arrays to hold student IDs and their idnumbers
        $choiceCounts = $choices->mapWithKeys(function ($choice) {
            return [$choice->id => 0];
        })->toArray();

        $choiceStudentData = $choices->mapWithKeys(function ($choice) {
            return [$choice->id => []]; // Initialize empty arrays for student data
        })->toArray();

        // Count the number of students who chose each choice
        $studentsWhoAnswered = AnsweredQuestion::where('tblquestion_id', $questionId)
            ->whereHas('tblquestion', function ($query) use ($examId) {
                $query->where('tblschedule_id', $examId); // Ensure `tblschedule_id` is used from `tblquestion` model
            })
           // ->with('user') // Load the related user model to access idnumber
            ->get();

        foreach ($studentsWhoAnswered as $answeredQuestion) {
            if (isset($choiceCounts[$answeredQuestion->addchoices_id])) {
                $choiceCounts[$answeredQuestion->addchoices_id]++;


                $user = DB::table('users')->where('id', $answeredQuestion->users_id)->first();
                $idnumber = $user ? $user->idnumber : null;
                $lname = $user ? $user->lname : null;
                $fname = $user ? $user->fname : null;
                $mname = $user ? $user->mname : null;
                
                // Store the student data (user ID and idnumber) who selected this choice
                $choiceStudentData[$answeredQuestion->addchoices_id][] = [
                    'user_id' => $answeredQuestion->users_id,
                    'idnumber' => $idnumber,
                    'lname' => $lname,
                    'fname' => $fname,
                    'mname' => $mname
                   // 'idnumber' => $answeredQuestion->user->idnumber // Assuming 'user' relation has 'idnumber'
                ];
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
        $choicesWithPercentage = $choices->map(function ($choice) use ($choiceCounts, $totalAnswered, $choiceStudentData) {
            $count = $choiceCounts[$choice->id] ?? 0;
            $percentage = $totalAnswered > 0 ? ($count / $totalAnswered) * 100 : 0;
            return [
                'choice' => $choice->choices,
                'count' => $count,
                'percentage' => round($percentage, 2) . '%', // Append % sign and round to 2 decimal places
                'students' => $choiceStudentData[$choice->id] // Include the student data (ID and idnumber) who chose this answer
            ];
        });

        // Calculate difficulty percentage
        $difficultyPercentage = $totalAnswered > 0 ? (1 - ($correctAnswersCount / $totalAnswered)) * 100 : 0;

        $difficultyCategory = '';
        if ($difficultyPercentage < 30) {
            $difficultyCategory = 'Easy';
        } elseif ($difficultyPercentage >= 30 && $difficultyPercentage < 70) {
            $difficultyCategory = 'Moderately Difficult';
        } else {
            $difficultyCategory = 'Difficult';
        }

        $itemAnalysis[] = [
            'question_id' => $questionId,
            'question' => $question->question,
            'choices' => $choicesWithPercentage,
            'correct_answer' => $correctAnswerText,
            'difficulty_percentage' => round($difficultyPercentage, 2) . '%', // Add difficulty percentage to the response
            'difficulty_category' => $difficultyCategory // Add difficulty category
        ];
    }

    return response()->json([
        'exam_title' => $subjectTitle,
        'instruction' => $instructionText,
        'item_analysis' => $itemAnalysis,
        'total_students' => $totalStudents,
        'students_completed_exam' => $studentsCompletedCount,
        'completion_percentage' => round($completionPercentage, 2) . '%', // Percentage of students who completed the exam
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
                'tblschedule.points_exam',
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
        $currentDateTime = Carbon::now('Asia/Manila');
        $examStartDateTime = Carbon::parse($exam->start)->setTimezone('Asia/Manila');

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



public function viewquestion(Request $request, $questionId)
{
    // Get the question and its related choices, including the correct answer
    $questionData = DB::table('tblquestion')
        ->leftJoin('addchoices', 'tblquestion.id', '=', 'addchoices.tblquestion_id') // Join with choices
        ->leftJoin('correctanswer', 'tblquestion.id', '=', 'correctanswer.tblquestion_id') // Join with correct answer
        ->where('tblquestion.id', $questionId) // Match the question ID
        ->select(
            'tblquestion.id as question_id',        // Select question ID
            'tblquestion.question',
            'addchoices.id as choice_id',           // Select choice ID
            'addchoices.choices',
            'correctanswer.addchoices_id as correct_choice_id', // Get the ID of the correct choice
            'correctanswer.correct_answer',          // Get the correct answer text
            'correctanswer.points'                    // Get points for the correct answer
        )
        ->get();

    // Check if the question exists
    if ($questionData->isEmpty()) {
        return response()->json([
            'message' => 'Question not found.'
        ], 404);
    }

    // Structure the response
    $response = [
        'question_id' => $questionData->first()->question_id, // Get the question ID
        'question' => $questionData->first()->question,        // Get the question text
        'choices' => $questionData->map(function ($item) {
            return [
                'choice_id' => $item->choice_id,   // Include choice ID
                'choice' => $item->choices          // Include choice text
            ];
        }),
        'correct_answer' => $questionData->first()->correct_answer, // Get correct answer
        'correct_choice_id' => $questionData->first()->correct_choice_id, // Get correct choice ID
        'points' => $questionData->first()->points      // Get points for the correct answer
    ];

    return response()->json($response, 200);
}



public function updateQuestion(Request $request, $questionId)
{
    // Validation rules for question only
    $request->validate([
        'question' => 'sometimes|string',  // Only validate the question
    ]);

    try {
        // Find the question by ID
        $question = Question::find($questionId);

        // Return 404 if question is not found
        if (!$question) {
            return response()->json(['message' => 'Question not found.'], 404);
        }

        // Update the question fields if provided
        $question->update($request->only(['question']));

        return response()->json(['message' => 'Question updated successfully.'], 200);
    } catch (\Exception $e) {
        // Log the error for debugging
        Log::error('Failed to update question: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to update question.'], 500);
    }
}

public function updateChoice(Request $request, $questionId, $choiceId)
{
    // Validation rules for updating a single choice
    $request->validate([
        'choice' => 'required|string',  // Ensure the new choice is a string
    ]);

    try {
        // Find the question to ensure it exists
        $question = Question::find($questionId);

        // Return 404 if the question is not found
        if (!$question) {
            return response()->json(['message' => 'Question not found.'], 404);
        }

        // Find the specific choice by ID
        $choice = Choice::where('tblquestion_id', $questionId)->find($choiceId);

        // Return 404 if the choice is not found
        if (!$choice) {
            return response()->json(['message' => "Choice with ID $choiceId not found."], 404);
        }

        // Update the choice with the new value
        $choice->update(['choices' => $request->choice]);

        return response()->json(['message' => 'Choice updated successfully.'], 200);
    } catch (\Exception $e) {
        // Log the error for debugging
        Log::error('Failed to update choice: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to update choice.'], 500);
    }
}


public function updateCorrectAnswer(Request $request, $id)
{
    // Validate the incoming request
    $request->validate([
        'addchoices_id' => 'nullable|integer|exists:addchoices,id', // Ensure addchoices_id exists in addchoices table
        'correct_answer' => 'sometimes|string',
        'points' => 'nullable|integer',
    ]);

    try {
        // Find the correct answer by its ID
        $correctAnswer = CorrectAnswer::find($id);

        if (!$correctAnswer) {
            return response()->json(['message' => 'Correct answer not found.'], 404);
        }

        // Update the fields that are present in the request
        $correctAnswer->update($request->only(['addchoices_id','correct_answer', 'points']));
        
        return response()->json([
            'message' => 'Correct answer updated successfully.',
            'data' => $correctAnswer
        ], 200);
    } catch (\Exception $e) {
        // Log the error and return an appropriate response
        Log::error('Failed to update correct answer: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to update correct answer.'], 500);
    }
}


public function itemAnalysis2(Request $request)
{
    // Validate the request
    $request->validate([
        'examId' => 'required|integer',
    ]);

    $examId = $request->input('examId');

    // Fetch the exam details and automatically get the class ID
    $examSchedule = Exam::where('id', $examId)->firstOrFail();
    $classId = $examSchedule->classtable_id;

    $instruction = Instructions::where('schedule_id', $examId)->first();
    $instructionText = $instruction ? $instruction->instruction : 'No instructions provided.';

    $subjectTitle = $examSchedule->title ? $examSchedule->title : 'No subject title available';

    // Retrieve all students in the class
    $students = JoinClass::where('class_id', $classId)
        ->where('status', 1) // Only get active students
        ->with('user') // Load the related user model
        ->get();

    $totalStudents = $students->count(); // Total number of students in the class

    // Retrieve all students who answered at least one question in the exam
    $studentsWhoCompleted = AnsweredQuestion::whereHas('tblquestion', function ($query) use ($examId) {
        $query->where('tblschedule_id', $examId);
    })
    ->groupBy('users_id')
    ->pluck('users_id');

    $studentsCompletedCount = $studentsWhoCompleted->count(); // Number of students who took the exam

    // Calculate the percentage of students who completed the exam
    $completionPercentage = $totalStudents > 0 ? ($studentsCompletedCount / $totalStudents) * 100 : 0;

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

        // Initialize counts and arrays to hold student IDs and their idnumbers
        $choiceCounts = $choices->mapWithKeys(function ($choice) {
            return [$choice->id => 0];
        })->toArray();

        $choiceStudentData = $choices->mapWithKeys(function ($choice) {
            return [$choice->id => []]; // Initialize empty arrays for student data
        })->toArray();

        // Count the number of students who chose each choice
        $studentsWhoAnswered = AnsweredQuestion::where('tblquestion_id', $questionId)
            ->whereHas('tblquestion', function ($query) use ($examId) {
                $query->where('tblschedule_id', $examId);
            })
            ->get();

        foreach ($studentsWhoAnswered as $answeredQuestion) {
            if (isset($choiceCounts[$answeredQuestion->addchoices_id])) {
                $choiceCounts[$answeredQuestion->addchoices_id]++;

                $user = DB::table('users')->where('id', $answeredQuestion->users_id)->first();
                $idnumber = $user ? $user->idnumber : null;
                $lname = $user ? $user->lname : null;
                $fname = $user ? $user->fname : null;
                $mname = $user ? $user->mname : null;

                // Store the student data (user ID and idnumber) who selected this choice
                $choiceStudentData[$answeredQuestion->addchoices_id][] = [
                    'user_id' => $answeredQuestion->users_id,
                    'idnumber' => $idnumber,
                    'lname' => $lname,
                    'fname' => $fname,
                    'mname' => $mname
                ];
            }
        }

        // Get the correct answer for the question
        $correctAnswer = CorrectAnswer::where('tblquestion_id', $questionId)->first();
        $correctAnswerId = $correctAnswer ? $correctAnswer->addchoices_id : null;

        // Count the number of correct answers
        $correctAnswersCount = AnsweredQuestion::where('tblquestion_id', $questionId)
            ->where('addchoices_id', $correctAnswerId)
            ->count();

        // Calculate percentages and format them with %
        $totalAnswered = count($studentsWhoAnswered);
        $choicesWithPercentage = $choices->map(function ($choice) use ($choiceCounts, $totalAnswered, $choiceStudentData) {
            $count = $choiceCounts[$choice->id] ?? 0;
            $percentage = $totalAnswered > 0 ? ($count / $totalAnswered) * 100 : 0;
            return [
                'choice' => $choice->choices,
                'count' => $count,
                'percentage' => round($percentage, 2) . '%',
                'students' => $choiceStudentData[$choice->id]
            ];
        });

        $studentIds = $studentsWhoAnswered->pluck('users_id');
        $formTotal = $totalAnswered > 0 ? round($totalAnswered * 0.27) : 0;
        $results = DB::table('tblresult')
            ->whereIn('users_id', $studentIds)
            ->where('exam_id', $examId)
            ->select('users_id', 'total_score')
            ->orderBy('total_score', 'desc')
            ->get();

        // Get top 3 highest scores
        $top3Highest = DB::table('tblresult')
            ->whereIn('users_id', $studentIds)
            ->where('exam_id', $examId)
            ->orderBy('total_score', 'desc')
            ->take($formTotal)
            ->get();

        // Get top 3 lowest scores
        $top3Lowest = DB::table('tblresult')
            ->whereIn('users_id', $studentIds)
            ->where('exam_id', $examId)
            ->orderBy('total_score', 'asc')
            ->take($formTotal)
            ->get();

        // Count correct answers for top 3 highest scorers
        $top3Highest = $top3Highest->map(function ($result) use ($examId, $questionId) {
            // Count the correct answers given by the user for the specific question
            $correctAnswersCount = DB::table('answered_question')
                ->where('users_id', $result->users_id) // Ensure you use tblstudent_id for the user
                ->where('tblquestion_id', $questionId) // Filter by the specific question ID
                ->where('addchoices_id', function ($query) use ($questionId) {
                    // Get the correct answer ID for the specified question
                    $query->select('addchoices_id')
                        ->from('correctanswer')
                        ->where('tblquestion_id', $questionId);
                })
                ->count();

        
            return [
                'user_id' => $result->users_id,
                'total_score' => $result->total_score,
                'correct_answers' => $correctAnswersCount // Add correct answers count
            ];
        });
        
        // Count correct answers for top 3 lowest scorers
        $top3Lowest = $top3Lowest->map(function ($result) use ($examId, $questionId) {
            // Count the correct answers given by the user for the specific question
            $correctAnswersCount = DB::table('answered_question')
                ->where('users_id', $result->users_id) // Ensure you use tblstudent_id for the user
                ->where('tblquestion_id', $questionId) // Filter by the specific question ID
                ->where('addchoices_id', function ($query) use ($questionId) {
                    // Get the correct answer ID for the specified question
                    $query->select('addchoices_id')
                        ->from('correctanswer')
                        ->where('tblquestion_id', $questionId);
                })
                ->count();
        
            return [
                'user_id' => $result->users_id,
                'total_score' => $result->total_score,
                'correct_answers' => $correctAnswersCount // Add correct answers count
            ];

            
           
        });
        $phTotal = $top3Highest->sum('correct_answers');
        $plTotal = $top3Lowest->sum('correct_answers');

        $divideph = $formTotal > 0 ? round(($phTotal / $formTotal) * 100) : 0;
        $dividepl = $formTotal > 0 ? round(($plTotal / $formTotal) * 100) : 0;

        $itemdifficulty =  (($divideph+ $dividepl)/2 );
        $difficultyCategory = '';


        if ($itemdifficulty < 24) {
            $difficultyCategory = 'Difficult';
        } elseif ($itemdifficulty >= 25 && $itemdifficulty < 75) {
            $difficultyCategory = 'Average item';
        } else {
            $difficultyCategory = 'Easy';
        }



        $itemdiscrimination = ($divideph - $dividepl);
        $discriminationCategory = '';
        if ($itemdiscrimination < 10) {
            $discriminationCategory = 'Poor Item';
        } elseif ($itemdiscrimination >= 11 && $itemdiscrimination < 19) {
            $discriminationCategory = 'Marginal Item';
        } 
        elseif ($itemdiscrimination >= 20 && $itemdiscrimination < 29) {
            $discriminationCategory = 'Reasonable Good Item';
        } elseif ($itemdiscrimination >= 30 && $itemdiscrimination < 39) {
            $discriminationCategory = 'Good Item';
        } 
        else {
            $discriminationCategory = 'Very Good Item';
        }

        $difficultyCategory2='';
        $discriminationCategory2='';
        $decision='';
        if ($itemdifficulty < 24) {
            $difficultyCategory2 = 'Difficult';
            
                if ($itemdiscrimination < 10) {
                    $discriminationCategory2 = 'Poor Item';
                    $decision='Reject';
                } elseif ($itemdiscrimination >= 11 && $itemdiscrimination < 19) {
                    $discriminationCategory2 = 'Marginal Item';
                    $decision='Reject';
                } 
                elseif ($itemdiscrimination >= 20 && $itemdiscrimination < 29) {
                    $discriminationCategory2 = 'Reasonable Good Item';
                    $decision='Revise';
                } elseif ($itemdiscrimination >= 30 && $itemdiscrimination < 39) {
                    $discriminationCategory2 = 'Good Item';
                    $decision='Revise';
                } 
                else {
                    $discriminationCategory2 = 'Very Good Item';
                    $decision='Revise';
                }
        } elseif ($itemdifficulty >= 25 && $itemdifficulty < 75) {
            $difficultyCategory2 = 'Average item';
           
            if ($itemdiscrimination < 10) {
                $discriminationCategory2 = 'Poor Item';
                $decision='Revise';
            } elseif ($itemdiscrimination >= 11 && $itemdiscrimination < 19) {
                $discriminationCategory2 = 'Marginal Item';
                $decision='Revise';
            } 
            elseif ($itemdiscrimination >= 20 && $itemdiscrimination < 29) {
                $discriminationCategory2 = 'Reasonable Good Item';
                $decision='Retain';
            } elseif ($itemdiscrimination >= 30 && $itemdiscrimination < 39) {
                $discriminationCategory2 = 'Good Item';
                $decision='Retain';
            } 
            else {
                $discriminationCategory2 = 'Very Good Item';
                $decision='Retain';
            }
            
        } else {
            $difficultyCategory2 = 'Easy';
           
            if ($itemdiscrimination < 10) {
                $discriminationCategory2 = 'Poor Item';
                $decision='Reject';
            } elseif ($itemdiscrimination >= 11 && $itemdiscrimination < 19) {
                $discriminationCategory2 = 'Marginal Item';
                $decision='Reject';
            } 
            elseif ($itemdiscrimination >= 20 && $itemdiscrimination < 29) {
                $discriminationCategory2 = 'Reasonable Good Item';
                $decision='Revise';
            } elseif ($itemdiscrimination >= 30 && $itemdiscrimination < 39) {
                $discriminationCategory2 = 'Good Item';
                $decision='Revise';
            } 
            else {
                $discriminationCategory2 = 'Very Good Item';
                $decision='Revise';
            }
        }



        


        $itemAnalysis[] = [
            'question_id' => $questionId,
            'question' => $question->question,
            'choices' => $choicesWithPercentage,
            'correct_answer' => $correctAnswer ? $correctAnswer->addchoices->choices : null,
            'totalstudent' => count($studentsWhoAnswered),
            'totalFormula' =>$formTotal,
            'studentWscore' => $results->map(function ($result) {
                return [
                    'user_id' => $result->users_id,
                    'total_score' => $result->total_score,
                ];
            }),
            'top3High' => $top3Highest,
            'top3Low' => $top3Lowest,
            'top3Hightotal' => $phTotal,
            'top3Lowtotal' => $plTotal,
             'totalcomputeWpercentph' =>$divideph,
             'totalcomputeWpercentpl' =>$dividepl,
             'itemdifficulty'=> round($itemdifficulty, 0) . '%',
             'DifficultyCategory'=>$difficultyCategory,
             'itemdiscrimination'=> round($itemdiscrimination, 0). '%',
             'DiscriminationCategory'=>$discriminationCategory,
             'levelofDifficulty'=>$difficultyCategory2,
             'DiscriminationLevel'=>$discriminationCategory2,
             'Decision'=>$decision,


        ];
    }

    return response()->json([
        'exam_title' => $subjectTitle,
        'instruction' => $instructionText,
        'item_analysis' => $itemAnalysis,
        'total_students' => $totalStudents,
        'students_completed_exam' => $studentsCompletedCount,
        'completion_percentage' => round($completionPercentage, 2) . '%',
    ], 200);
}
public function downloadExamInstructionsPDF($examId)
{
    try {
        \Log::info("Starting PDF generation for Exam ID: $examId");

        if (empty($examId)) {
            \Log::error("Exam ID is empty.");
            return response()->json(['error' => 'Exam ID is required.'], 400);
        }

        // Fetch the exam and related details
        $exam = Exam::findOrFail($examId);
        \Log::info("Exam found: " . $exam->id);

        // Gather additional exam details
        $title = $exam->title;
        $pointsExam = $exam->points_exam;
        $quarter = $exam->quarter;
        $start = $exam->start;
        $status = $exam->status;

        $instructions = Instructions::where('schedule_id', $examId)
                                    ->with(['questions.correctAnswers', 'questions.choices'])
                                    ->get();
        \Log::info("Fetched instructions for Exam ID: $examId, Total: " . count($instructions));

        // Start PDF creation
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Exam Instructions for ID: ' . $examId, 0, 1);

        // Add upper portion with exam details
        $pdf->SetFont('Arial', '', 12);
        $pdf->Ln(5);
        $pdf->Cell(0, 10, "Title: $title", 0, 1);
        $pdf->Cell(0, 10, "Points: $pointsExam", 0, 1);
        $pdf->Cell(0, 10, "Quarter: $quarter", 0, 1);
        $pdf->Cell(0, 10, "Start Date: $start", 0, 1);
        $pdf->Cell(0, 10, "Status: " . ($status == '1' ? 'Active' : 'Inactive'), 0, 1);

        // Add instructions section with unique filtering
        $processedInstructions = [];
        foreach ($instructions as $instruction) {
            if (in_array($instruction->description, $processedInstructions)) {
                continue;
            }
            $processedInstructions[] = $instruction->description;
            $pdf->Ln();
            $pdf->MultiCell(0, 10, $instruction->description);
        }

        // Add questions section with unique filtering
        $questions = $instructions->flatMap(function($instruction) {
            return $instruction->questions;
        });

        if ($questions->isNotEmpty()) {
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, 'Questions:', 0, 1);

            $processedQuestions = [];
            foreach ($questions as $question) {
                if (in_array($question->question, $processedQuestions)) {
                    continue;
                }
                $processedQuestions[] = $question->question;
                $pdf->SetFont('Arial', '', 12);
                $pdf->Ln();
                $pdf->MultiCell(0, 10, 'Q: ' . $question->question);
                
                if ($question->choices) {
                    foreach ($question->choices as $choice) {
                        $pdf->MultiCell(0, 10, ' - ' . $choice->choices);
                    }
                }

                if ($question->correctAnswers && $question->correctAnswers->isNotEmpty()) {
                    $correctAnswer = $question->correctAnswers->pluck('correct_answer')->join(', ');
                    $pdf->SetFont('Arial', 'I', 12);
                    $pdf->Ln();
                    $pdf->MultiCell(0, 10, 'Correct Answer: ' . $correctAnswer);
                }
            }
        } else {
            $pdf->Ln();
            $pdf->Cell(0, 10, 'No questions found for this exam.');
        }

        // Save PDF to storage and return download response
        $filePath = storage_path('app/public/exam_instructions_' . $examId . '.pdf');
        $pdf->Output('F', $filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);

    } catch (\Exception $e) {
        \Log::error("Error in PDF generation: " . $e->getMessage());
        return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
    }
}

public function export_result(Request $request)
{
    try {
        $teacher = Auth::user();

        // Validate that classtable_id is provided in the request
        $request->validate([
            'classtable_id' => 'required|integer'
        ]);

        // Retrieve all student results for the specified class (your current query logic)
        $results = DB::table('users')
            ->leftJoin('joinclass', 'users.id', '=', 'joinclass.user_id')
            ->leftJoin('tblschedule', function ($join) use ($request) {
                $join->on('tblschedule.classtable_id', '=', 'joinclass.class_id')
                     ->where('tblschedule.classtable_id', $request->classtable_id);
            })
            ->leftJoin('tblresult', function ($join) {
                $join->on('tblresult.users_id', '=', 'users.id')
                     ->on('tblresult.exam_id', '=', 'tblschedule.id');
            })
            ->leftJoin('tblstudent', 'users.id', '=', 'tblstudent.user_id')
            ->leftJoin('tblstrand', 'tblstudent.strand_id', '=', 'tblstrand.id')
            ->join('tblsection', 'tblstudent.section_id', '=', 'tblsection.id')
            ->select(
                'users.id AS student_id',
                'users.idnumber AS Lrn_id',
                'users.lname AS Last_name',
                'users.fname AS First_name',
                'users.mname AS Middle_i',
                'users.sex AS sex',
                'tblstrand.addstrand AS strand_name',
                'tblstrand.grade_level AS gradelevel_name',
                'tblsection.section',
                'tblschedule.title AS exam_title',
                'tblschedule.start',
                'tblschedule.end',
                'tblschedule.points_exam',
                'tblresult.total_score',
                'tblresult.average',
                'tblresult.total_exam',
                'tblresult.status',
                'tblresult.created_at',
                'tblresult.updated_at'
            )
            ->where('joinclass.class_id', $request->classtable_id)
            ->where('tblschedule.classtable_id', $request->classtable_id)
            ->where('joinclass.status', 1)
            ->where('tblschedule.id', '!=', null)
            ->orderBy('tblschedule.title') // Order by exam title first
            ->orderBy('users.lname')
            ->orderBy('users.fname')
            ->get();

        // If no results, return 404
        if ($results->isEmpty()) {
            return response()->json([
                'message' => 'No exam results found for this class.'
            ], 404);
        }

        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Initialize row counter
        $row = 1;

        // Group results by exam title
        $groupedResults = $results->groupBy('exam_title');

        foreach ($groupedResults as $examTitle => $students) {
            // Set exam title header
            $sheet->setCellValue("A{$row}", "Exam Title: $examTitle");
            $sheet->mergeCells("A{$row}:Q{$row}"); // Adjust the range based on your columns
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            // Set headers for student results
            $headers = [
                'Student ID', 'LRN ID', 'Last Name', 'First Name', 'Middle Initial', 'Sex', 
                'Strand', 'Grade Level', 'Section', 'Start', 'End', 
                 'Total Score', 'Average', 'Total Points Exam', 'Status'
            ];
            $sheet->fromArray($headers, NULL, "A{$row}");
            $row++;

            // Insert data into spreadsheet
            foreach ($students as $student) {
                $sheet->fromArray([
                    $student->student_id, 
                    $student->Lrn_id, 
                    $student->Last_name, 
                    $student->First_name,
                    $student->Middle_i, 
                    $student->sex, 
                    $student->strand_name, 
                    $student->gradelevel_name,
                    $student->section, 
                    $student->start, 
                    $student->end,
                   // $student->points_exam, 
                    $student->total_score, 
                    $student->average, 
                    //$student->total_exam, 
                    $student->points_exam, 
                    ($student->status === null) ? 'N/A' : (($student->status == 1) ? 'Passed' : 'Failed')
                ], NULL, "A{$row}");
                $row++;
            }

            // Add a blank row for spacing between exams
            $row++;
        }

        // Format LRN ID column (B) as a number with 0 decimals
        $sheet->getStyle("B2:B{$row}")
              ->getNumberFormat()
              ->setFormatCode('0');

        // Set filename
        $filename = "Student_Results_Class_{$request->classtable_id}.xlsx";

        // Generate file and download response
        $writer = new Xlsx($spreadsheet);
        $response = response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename);

        // Set headers for download
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', "attachment;filename=\"$filename\"");
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to retrieve exam results.',
            'exception' => $e->getMessage()
        ], 500);
    }
}



}