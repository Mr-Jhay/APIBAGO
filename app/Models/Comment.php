<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $table = 'recomendation_suggestion';

    protected $fillable = ['user_id', 'exam_id', 'comment'];

    // Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define the relationship with the Exam model
    public function exam()
    {
        return $this->belongsTo(tblschedule::class, 'exam_id');
    }
}
