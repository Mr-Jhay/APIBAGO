<?php

namespace App\Http\Controllers;

use App\Models\tblstudent;
use Illuminate\Http\Request;
use Fpdf\Fpdf;

class ReportController extends Controller
{
    public function generatePDF(Request $request)
    {
        // Fetch students based on filters (gender, strand, etc.)
        $students = $this->filterStudents($request);

        // Create PDF using FPDF
        $pdf = new Fpdf();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);

        // Header
        $pdf->Cell(10, 15, 'No.', 1);
        $pdf->Cell(30, 15, 'LRN', 1);
        $pdf->Cell(70, 15, 'Name', 1);
        $pdf->Cell(30, 15, 'Sex', 1);
        $pdf->Cell(50, 15, 'Email', 1);
        $pdf->Ln();

        // Data
        $i = 1;
        foreach ($students as $student) {
            $pdf->Cell(10, 15, $i++, 1);
            $pdf->Cell(30, 15, $student->user->idnumber, 1);
            $pdf->Cell(70, 15, $student->user->lname . ', ' . $student->user->fname . ' ' . $student->user->mname, 1);
            $pdf->Cell(30, 15, ucfirst($student->user->sex), 1);
            $pdf->Cell(50, 15, $student->user->email, 1);
            $pdf->Ln();
        }

        // Output PDF
        return response()->streamDownload(function() use ($pdf) {
            $pdf->Output('D', 'student_report.pdf');
        }, 'student_report.pdf');
    }

    private function filterStudents(Request $request)
    {
        return tblstudent::with(['user', 'strands', 'section'])
            ->when($request->gender && $request->gender !== 'all', function ($query) use ($request) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('sex', $request->gender);
                });
            })
            ->when($request->strand, function ($query) use ($request) {
                $query->where('strand_id', $request->strand);
            })
            ->get();
    }
}
