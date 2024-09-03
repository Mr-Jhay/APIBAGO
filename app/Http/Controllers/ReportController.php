<?php

namespace App\Http\Controllers;

use App\Models\tblstudent;
use App\Models\tblstrand;
use App\Models\tblsection;
use Illuminate\Http\Request;
use Fpdf\Fpdf;

class ReportController extends Controller
{
    public function generatePDF(Request $request)
    {
        // Fetch students based on filters (gender, strand, etc.)
        $students = $this->filterStudents($request);

        // Create PDF using FPDF
        $pdf = new Fpdf('L', 'mm', 'A4'); // Landscape orientation, millimeters, A4 size
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 8); // Smaller font size

        // Set margins
        $pdf->SetMargins(10, 20, 10); // left, top, right margins

        // Add Header
        $this->Header($pdf);

        // Column widths
        $widths = [15, 30, 60, 20, 50, 40, 40]; // Widths of the columns in mm

        // Table Header
        $pdf->SetFont('Arial', 'B', 8);
        foreach ($widths as $width) {
            $pdf->Cell($width, 10, $this->getColumnHeader($width), 1);
        }
        $pdf->Ln();

        // Data
        $pdf->SetFont('Arial', '', 8);
        $i = 1;
        foreach ($students as $student) {
            $pdf->Cell($widths[0], 10, $i++, 1);
            $pdf->Cell($widths[1], 10, $student->user->idnumber, 1);
            $pdf->Cell($widths[2], 10, $student->user->lname . ', ' . $student->user->fname . ' ' . $student->user->mname, 1);
            $pdf->Cell($widths[3], 10, ucfirst($student->user->sex), 1);
            $pdf->Cell($widths[4], 10, $student->user->email, 1);
            $pdf->Cell($widths[5], 10, $student->strands->addstrand ?? 'N/A', 1);
            $pdf->Cell($widths[6], 10, $student->section->section ?? 'N/A', 1);
            $pdf->Ln();

            // Check if page needs to break
            if ($pdf->GetY() > 250) { // 250mm is a rough estimate for space left on the page
                $pdf->AddPage();
                $this->Header($pdf);
                $pdf->SetFont('Arial', 'B', 8);
                foreach ($widths as $width) {
                    $pdf->Cell($width, 10, $this->getColumnHeader($width), 1);
                }
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 8);
            }
        }

        // Output PDF
        return response()->streamDownload(function() use ($pdf) {
            $pdf->Output('D', 'student_report.pdf');
        }, 'student_report.pdf');
    }

    private function Header($pdf)
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Republic of the Philippines', 0, 1, 'C');
        $pdf->Cell(0, 10, 'ECHAGUE NATIONAL HIGH SCHOOL - SENIOR HIGH SCHOOL', 0, 1, 'C');
        $pdf->Cell(0, 10, 'San Fabian Echague Isabela', 0, 1, 'C');
        $pdf->Ln(10); // Add some space after the header
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'LIST OF STUDENTS', 0, 1, 'C');
        $pdf->Ln(5); // Add some space before the table headers
    }

    private function getColumnHeader($width)
    {
        $headers = [
            15 => 'No.',
            30 => 'LRN',
            60 => 'Name',
            20 => 'Sex',
            50 => 'Email',
            40 => 'Strand',
            40 => 'Section'
        ];

        return $headers[$width] ?? '';
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
            ->when($request->section, function ($query) use ($request) {
                $query->where('section_id', $request->section);
            })
            ->get();
    }
}
