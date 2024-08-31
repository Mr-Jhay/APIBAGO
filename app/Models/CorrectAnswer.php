<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectAnswer extends Model
{
    use HasFactory;
    protected $table = 'correctanswer';

    protected $fillable = [
        'tblquestion_id',
        'addchoices_id',
        'correct_answer'
    ];
}
