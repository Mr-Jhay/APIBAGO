<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $table = 'tblschedule';

    protected $fillable = [
        'classtable_id',
        'title',
        'quarter',
        'start',
        'end'
    ];

    public function questions()
    {
        return $this->hasMany(Question::class, 'tblschedule_id');
    }






}