<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class TblTeacher extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    // Define the table name if it's not following Laravel's pluralization convention
    protected $table = 'tblteacher';

    // Define the fillable attributes
    protected $fillable = [
        'user_id',
        'position_id',
    ];

    // Define the relationship with the strands table
    public function strands()
    {
        return $this->belongsToMany(tblstrand::class, 'teacher_strand', 'teacher_id', 'strand_id');
    }
    public function tblstrand()
    {
        return $this->belongsToMany(tblstrand::class, 'teacher_strand', 'teacher_id', 'strand_id');
    }

    // Optionally, define a relationship with the User model if applicable
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Optionally, define a relationship with the Position model if applicable
    public function position()
    {
        return $this->belongsTo(tblposition::class, 'position_id');
    }

}
