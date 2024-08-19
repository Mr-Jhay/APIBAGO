<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class tblstrand extends Model
{
       use HasFactory, Notifiable, HasApiTokens;
    protected $table = 'tblstrand';

    protected $fillable = [
           'addstrand',
           'grade_level',         
       ];
}
