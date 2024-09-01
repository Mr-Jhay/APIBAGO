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
        'question_type',
        'question'
    ];

    public function choices()
    {
        return $this->hasMany(Choice::class, 'tblquestion_id');
    }

    public function correctAnswer()
    {
        return $this->hasOne(CorrectAnswer::class, 'tblquestion_id');
    }
}
