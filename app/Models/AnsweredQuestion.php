<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnsweredQuestion extends Model
{
    use HasFactory;
    protected $table = 'answered_question';

    protected $fillable = [
        'users_id',
        'tblquestion_id',
       // 'correctanswer_id'
      'addchoices_id',
      'Student_answer',

    ];


    // Add this relationship to link AnsweredQuestion with Question
    public function question()
    {
        return $this->belongsTo(Question::class, 'tblquestion_id');
    }

    public function tblquestion()
    {
        return $this->belongsTo(Question::class, 'tblquestion_id');
    }

    // Define relationship to correctAnswer
    public function correctAnswer()
    {
        return $this->hasOne(CorrectAnswer::class, 'tblquestion_id', 'tblquestion_id')
            ->where('addchoices_id', $this->addchoices_id);
    }

    public function addchoices()
{
    return $this->belongsTo(Choice::class, 'addchoices_id');
}
}
