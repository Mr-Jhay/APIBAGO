<?php
namespace App\Imports;

use App\Models\User;
use App\Models\tblstudent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class StudentsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'idnumber' => $row['idnumber'],
                'fname' => $row['fname'],
                'mname' => $row['mname'],
                'lname' => $row['lname'],
                'sex' => $row['sex'],
                'usertype' => 'student',
                'email' => $row['email'],
                'password' => Hash::make($row['password']),
            ]);

            tblstudent::create([
                'user_id' => $user->id,
                'strand_id' => $row['strand_id'],
                'section_id' => $row['section_id'],
               // 'Mobile_no' => $row['mobile_no'] ?? null,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk registration failed for row: ' . json_encode($row) . ' Error: ' . $e->getMessage());
            throw $e;  // Rethrow the exception to stop further processing
        }
    }
}
