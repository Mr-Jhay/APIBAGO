<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Choice extends Model
{
    use HasFactory;
    protected $table = 'addchoices';

    protected $fillable = [
        'tblquestion_id',
        'choices'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'tblquestion_id');
    }

    public function instruction()
{
    return $this->belongsTo(instructions::class, 'tblschedule_id', 'schedule_id');
}

public function correctAnswers()
{
    return $this->hasOne(CorrectAnswer::class, 'tblquestion_id', 'tblquestion_id')
        ->where('addchoices_id', $this->addchoices_id);
}

public static function shuffleChoices($choices)
{
    return $choices->shuffle(); // Shuffle the collection of choices
}
}
 