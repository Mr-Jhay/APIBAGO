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

public function correctAnswer()
{
    return $this->hasOne(CorrectAnswer::class, 'tblquestion_id');
}



public function getPoints()
{
    $correctAnswer = $this->correctAnswer()->first();
    return $correctAnswer ? $correctAnswer->points : 0;
}

// Method to get shuffled choices
//public function getShuffledChoices()
//{
//    return $this->choices()->get()->shuffle();
//} 


public function scopeLimitedByPoints($query, $limit, $pointsLimit)
    {
        // Select questions and their correct answers with points
        $questions = $query->with('correctAnswer')
            ->get()
            ->sortBy(function ($question) {
                // This will help in sorting the questions based on points
                return $question->correctAnswer ? $question->correctAnswer->points : 0;
            })
            ->take($limit); // Limit the number of questions

        // Filter questions to stay within the points limit
        $totalPoints = 0;
        return $questions->filter(function ($question) use ($pointsLimit, &$totalPoints) {
            $points = $question->correctAnswer ? $question->correctAnswer->points : 0;

            if ($totalPoints + $points <= $pointsLimit) {
                $totalPoints += $points;
                return true;
            }

            return false;
        });
    }

    /**
     * Get shuffled choices for the question.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getShuffledChoices()
    {
        return $this->choices->shuffle();
    }
}
