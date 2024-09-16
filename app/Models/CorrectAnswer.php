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
        'correct_answer',
        'points',
    ];

    public function tblquestion()
    {
        return $this->belongsTo(Question::class, 'tblquestion_id');
    }
    public function question()
    {
        return $this->belongsTo(Question::class, 'tblquestion_id');
    }
    public function choices()
    {
         return $this->hasMany(Choice::class, 'tblquestion_id');
     }

     public function addchoices()
{
    return $this->belongsTo(Choice::class, 'addchoices_id');
}
}
