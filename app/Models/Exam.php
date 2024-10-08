<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $table = 'tblschedule';

    protected $fillable = [
        'classtable_id', 
        'title', 
        'quarter',
        'start',
        'end',
        'points_exam',
        'status'
        
    ];

    public function questions()
    {
        return $this->hasMany(Question::class, 'tblschedule_id','id');
    }

    public function choices()
    {
        return $this->hasMany(Question::class, 'addchoices','id');
    }

    public function instructions()
    {
        return $this->hasOne(instructions::class, 'schedule_id', 'id','instruction','question_type');
    }
    public function correctAnswers() // Change from correctAnswer to correctAnswers
    {
        return $this->hasMany(CorrectAnswer::class, 'tblquestion_id');
    }

    

    













}
