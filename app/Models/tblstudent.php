<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class tblstudent extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'tblteacher';

    protected $fillable = [
           'user_id',
           'section_id',  
           'strand_id',
           'gradelevel_id',
           'Mobile_no',    
       ];
}
