<?php 

namespace app\Imports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Http\Controllers\UsersController;
use App\Models\User;

class UserImport implements ToModel
{
    public function model(array $row)
    {
        // Assuming the first column is 'name' and second is 'email'
        return new User([
            'idnumber' => $row[0],
            'fname' => $row[1],
            'mname' => $row[2],
            'lname' => $row[3],
            'sex' => $row[4],
            'usertype' => $row[5],
            'password' => $row[6],
            // Add other fields as needed
        ]);
    }
}
