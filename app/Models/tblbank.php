<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tblbank extends Model
{
    protected $table = 'tblbank';

    protected $fillable = [
        'user_id',
        'subject_id',
        'question_id',
        'choice_id',
        'correct_id',
        'Quarter',
    ];

    public function question()
    {
        return $this->belongsTo(TblQuestion::class, 'question_id');
    }

    public function choices()
    {
        return $this->belongsTo(AddChoices::class, 'choice_id');
    }

    public function correct_answer()
    {
        return $this->belongsTo(CorrectAnswer::class, 'correct_id');
    }
}
