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
        'correctanswer_id'
    ];
}