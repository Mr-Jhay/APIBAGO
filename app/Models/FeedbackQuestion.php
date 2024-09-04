<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackQuestion extends Model
{
    use HasFactory;

    protected $table = 'tblfeedback';
    

    protected $fillable = [
        'class_id',
        'question',
    ];

    public function options()
    {
        return $this->hasMany(FeedbackOption::class, 'tblfeedback_id');
    }
}
