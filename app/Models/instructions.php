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
        
    ];
}
