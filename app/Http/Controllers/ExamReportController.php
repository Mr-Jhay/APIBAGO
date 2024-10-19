<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use Fpdf\Fpdf;

class ExamReportController extends Controller
{
    // Function to generate and download the PDF report
    public function downloadExamReport(Request $request)
    {
        $examId = $request->input('examId');

        // Fetch the exam details from the database
        $exam = Exam::with('instructions.questions.choices.correct_answers')->find($examId);

        if (!$exam) {
            return response()->json(['error' => 'Exam not found'], 404);
        }

        // Create a new FPDF instance
        $pdf = new Fpdf();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        // Add Title
        $pdf->Cell(40, 10, 'Exam Report');
        $pdf->Ln(10);

        // Add Exam Instruction
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(40, 10, 'Instruction:');
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(0, 10, $exam->instructions[0]->instruction);
        $pdf->Ln(10);

        // Loop through questions
        foreach ($exam->instructions as $instruction) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(40, 10, 'Questions:');
            $pdf->Ln(10);

            foreach ($instruction->questions as $index => $question) {
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(40, 10, ($index + 1) . ". " . $question->question);
                $pdf->Ln(7);

                // Display choices (if available)
                if ($question->choices) {
                    foreach ($question->choices as $choice) {
                        $pdf->Cell(40, 10, "- " . $choice->choices);
                        $pdf->Ln(5);
                    }
                }

                // Correct answer
                $pdf->SetFont('Arial', 'I', 12);
                $pdf->Cell(40, 10, "Correct Answer: " . $question->correct_answers[0]->correct_answer);
                $pdf->Ln(5);

                // Points
                $pdf->Cell(40, 10, "Points: " . $question->correct_answers[0]->points);
                $pdf->Ln(10);
            }
        }

        // Output PDF as a download
        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}
