<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class tblresult extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $table = 'tblresult';

    protected $fillable = [
           'users_id', 
           'exam_id',
           'total_score',
           'total_exam',
           'status',                
       ];
}
