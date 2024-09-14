<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $table = 'tblquestion';

    protected $fillable = [
        'tblschedule_id',
        'question',
    ];

   public function choices()
   {
        return $this->hasMany(Choice::class, 'tblquestion_id');
    }

    public function correctAnswers() // Change from correctAnswer to correctAnswers
    {
        return $this->hasMany(CorrectAnswer::class, 'tblquestion_id');
    }
    public function question()
{
    return $this->belongsTo(Question::class, 'tblquestion_id');
}
public function instruction()
{
    return $this->belongsTo(instructions::class, 'tblschedule_id', 'schedule_id');
}

public function answeredQuestions()
{
    return $this->hasMany(AnsweredQuestion::class, 'tblquestion_id');
}


    
}
