<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tblbank extends Model
{
    protected $table = 'studentexam';

    protected $fillable = [
        'user_id',
        'subject_id',
        'question_id',
        'choice_id',
        'correct_id',
        'Quarter',
    ];
}
