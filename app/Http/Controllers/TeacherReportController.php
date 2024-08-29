<?php

namespace App\Http\Controllers;

use App\Models\tblteacher;
use Illuminate\Http\Request;
use Fpdf\Fpdf;

class TeacherReportController extends Controller
{
    public function generateTeacherPDF(Request $request)
    {
        // Fetch teachers based on filters (gender, position, etc.)
        $teachers = $this->filterTeachers($request);

        // Create PDF using FPDF
        $pdf = new Fpdf();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);

        // Header
        $pdf->Cell(10, 15, 'No.', 1);
        $pdf->Cell(30, 15, 'LRN', 1);
        $pdf->Cell(65, 15, 'Name', 1);
        $pdf->Cell(30, 15, 'Sex', 1);
        $pdf->Cell(50, 15, 'Email', 1);
        $pdf->Cell(50, 15, 'Position', 1);
        $pdf->Ln();

        // Data
        $i = 1;
        foreach ($teachers as $teacher) {
            $pdf->Cell(10, 15, $i++, 1);
            $pdf->Cell(30, 15, $teacher->user->idnumber, 1);
            $pdf->Cell(65, 15, $teacher->user->lname . ', ' . $teacher->user->fname . ' ' . $teacher->user->mname, 1);
            $pdf->Cell(30, 15, ucfirst($teacher->user->sex), 1);
            $pdf->Cell(50, 15, $teacher->user->email, 1);
            $pdf->Cell(50, 15, $teacher->position->position_name, 1);
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
}
