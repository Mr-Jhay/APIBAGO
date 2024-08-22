<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class tblclass extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $table = 'tblclass';

    protected $fillable = [
           'user_id',  
           'strand_id',  
           'section_id',  
           'subject_id',  
           'class_desc',  
           'profile_img',  
           'gen_code',  
         
       ];
}
