<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnsweredQuestion extends Model
{
    use HasFactory;
    protected $table = 'answeredQuestion';

    protected $fillable = [
        'users_id',
        'tblquestion_id',
        'addchoices_id',
        'Student_answer',
    ];

    // Add this relationship to link AnsweredQuestion with Question
    public function question()
    {
        return $this->belongsTo(Question::class, 'tblquestion_id');
    }
}



