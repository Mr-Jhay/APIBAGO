<?php

namespace App\Http\Controllers;

use App\Models\tblteacher;
use App\Models\tblposition;
use Illuminate\Http\Request;
use Fpdf\Fpdf;

class TeacherReportController extends Controller
{
    public function generateTeacherPDF(Request $request)
    {
        // Fetch teachers based on filters (gender, position, etc.)
        $teachers = $this->filterTeachers($request);

        // Create PDF in Landscape orientation using FPDF
        $pdf = new Fpdf('L', 'mm', 'A4'); // 'L' for Landscape, 'A4' size
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);

        // Add logos to the PDF
        $this->addLogos($pdf);

        // Add header after logos
        $this->Header($pdf);

        // Column widths (in landscape mode)
        $widths = [15, 30, 65, 30, 50, 50];

        // Set color for the table header
        $pdf->SetFillColor(200, 200, 200); // Light gray
        $pdf->SetFont('Arial', 'B', 10);

        // Table Header with fill color
        $pdf->Cell($widths[0], 15, 'No.', 1, 0, 'C', true);
        $pdf->Cell($widths[1], 15, 'LRN', 1, 0, 'C', true);
        $pdf->Cell($widths[2], 15, 'Name', 1, 0, 'C', true);
        $pdf->Cell($widths[3], 15, 'Sex', 1, 0, 'C', true);
        // $pdf->Cell($widths[4], 15, 'Email', 1, 0, 'C', true);
        $pdf->Cell($widths[5], 15, 'Position', 1, 0, 'C', true);
        $pdf->Ln();

        // Table Data
        $pdf->SetFont('Arial', '', 10);
        $i = 1;
        foreach ($teachers as $teacher) {
            $pdf->Cell($widths[0], 15, $i++, 1);
            $pdf->Cell($widths[1], 15, $teacher->user->idnumber, 1);
            $pdf->Cell($widths[2], 15, $teacher->user->lname . ', ' . $teacher->user->fname . ' ' . $teacher->user->mname, 1);
            $pdf->Cell($widths[3], 15, ucfirst($teacher->user->sex), 1);
            // $pdf->Cell($widths[4], 15, $teacher->user->email, 1);
            $pdf->Cell($widths[5], 15, $teacher->position->teacher_postion, 1);
            $pdf->Ln();
        }

        // Output PDF
        return response()->streamDownload(function() use ($pdf) {
            $pdf->Output('D', 'teacher_report.pdf');
        }, 'teacher_report.pdf');
    }

    private function filterTeachers(Request $request)
    {
        return tblteacher::with(['user', 'position'])
            ->when($request->gender && $request->gender !== 'all', function ($query) use ($request) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('sex', $request->gender);
                });
            })
            ->when($request->position_id, function ($query) use ($request) {
                $query->where('position_id', $request->position_id);
            })
            ->get();
    }

    protected function addLogos($pdf)
    {
        // Path to logos (ensure the logos are in these paths)
        $leftLogoPath = storage_path('app/public/images/enhs.png');
        $rightLogoPath = storage_path('app/public/images/deped.png');

        // Add left logo
        if (file_exists($leftLogoPath)) {
            $pdf->Image($leftLogoPath, 10, 10, 30); // Adjust position (X: 10mm, Y: 10mm) and size (30mm width)
        }

        // Add right logo
        if (file_exists($rightLogoPath)) {
            $pdf->Image($rightLogoPath, 250, 10, 30); // Adjust position (X: 250mm, Y: 10mm) and size (30mm width)
        }

        $pdf->Ln(25); // Add some space after the logos
    }

    private function Header($pdf)
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Republic of the Philippines', 0, 1, 'C');
        $pdf->Cell(0, 10, 'ECHAGUE NATIONAL HIGH SCHOOL - SENIOR HIGH SCHOOL', 0, 1, 'C');
        $pdf->Cell(0, 10, 'San Fabian Echague Isabela', 0, 1, 'C');
        $pdf->Ln(10); // Add some space after the header
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'LIST OF TEACHERS', 0, 1, 'C');
        $pdf->Ln(5); // Add some space before the table headers
    }
}
