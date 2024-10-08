<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class instructions extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $table = 'exam_instructions';

    protected $fillable = [
        'schedule_id',
        'instruction',
        'question_type',
        
    ];


    public function exam()
    {
        return $this->belongsTo(Exam::class,'schedule_id','id');
    }
    public function questions()
    {
        return $this->hasMany(Question::class, 'tblschedule_id', 'schedule_id');
    }

    public function choices()
    {
         return $this->hasMany(Choice::class, 'tblquestion_id');
     }
     public function instructions()
     {
         return $this->hasOne(instructions::class, 'schedule_id', 'id');
     }
}
