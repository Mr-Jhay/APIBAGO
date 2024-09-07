<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class studentexam extends Model
{
    use HasFactory;

    protected $table = 'studentexam';

    protected $fillable = [
        'user_id',
        'question_type',
        'tblschedule_id'
    ];
}
