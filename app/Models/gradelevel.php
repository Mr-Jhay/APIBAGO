<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class gradelevel extends Model
{
    protected $table = 'gradelevel';

    protected $fillable = [
           'glevel',         
       ];
}
