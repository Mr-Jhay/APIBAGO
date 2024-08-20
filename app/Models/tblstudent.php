<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class tblstudent extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'tblstudent';

    protected $fillable = [
           'user_id',
           'strand_id',
           'section_id',  
           'Mobile_no',    
       ];
}
