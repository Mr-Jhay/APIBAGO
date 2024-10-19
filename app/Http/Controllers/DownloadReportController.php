<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;
use App\Models\Exam;
use Illuminate\Support\Facades\Log;

class DownloadReportController extends Controller
{
    // This method uses the existing logic to fetch instructions and generates a PDF
    public function downloadReport(Request $request)
    {
        $examId = $request->input('examId');  // Get the exam ID from the request
        
        try {
            // Fetch exam instructions and correct answers using your existing method
            $instructionsData = $this->getExamInstructionAndCorrectAnswers($examId);

            // Extract instructions from the response
            $instructions = $instructionsData->original['instructions'];
            
            if ($instructions->isEmpty()) {
                return response()->json(['error' => 'No data found for this exam.'], 404);
            }

            // Initialize FPDF
            $pdf = new Fpdf();
            $pdf->AddPage();
            
            // Set Font
            $pdf->SetFont('Arial', 'B', 16);
            
            // Report Title
            $pdf->Cell(190, 10, 'Exam Report', 1, 1, 'C');
            
            // Add exam details
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, "Exam ID: {$examId}", 0, 1);
            $pdf->Cell(0, 10, 'Total Instructions: ' . $instructions->count(), 0, 1);
            
            // Table header for questions
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(10, 10, '#', 1);
            $pdf->Cell(120, 10, 'Question', 1);
            $pdf->Cell(30, 10, 'Correct Answer', 1);
            $pdf->Cell(30, 10, 'Points', 1);
            $pdf->Ln();

            // Iterate through instructions and questions
            $pdf->SetFont('Arial', '', 12);
            $questionNumber = 1;

            foreach ($instructions as $instruction) {
                foreach ($instruction->questions as $question) {
                    // Fetch correct answer and points
                    $correctAnswer = $question->correctAnswers[0]->correct_answer ?? 'N/A';
                    $points = $question->correctAnswers[0]->points ?? 'N/A';

                    // Print question data in table
                    $pdf->Cell(10, 10, $questionNumber++, 1);
                    $pdf->Cell(120, 10, $question->question, 1);
                    $pdf->Cell(30, 10, $correctAnswer, 1);
                    $pdf->Cell(30, 10, $points, 1);
                    $pdf->Ln();
                }
            }

            // Output the PDF for download
            $pdf->Output('D', 'exam_report.pdf');
        } catch (\Exception $e) {
            Log::error('Error generating report: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate report.'], 500);
        }
    }

    // Your existing method to fetch instructions and correct answers
    public function getExamInstructionAndCorrectAnswers($exam_id) 
    {
        try {
            $instructions = Exam::with(['instructions.questions.choices', 'instructions.questions.correctAnswers'])
                ->where('id', $exam_id)
                ->where('status', 1) // Check if the exam is published
                ->get();

            return response()->json([
                'instructions' => $instructions,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve exam questions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve exam questions.'. $e->getMessage()], 500);
        }
    }
}
