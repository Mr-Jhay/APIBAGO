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
          
       ];

       public function user()
       {
           return $this->belongsTo(User::class, 'user_id');
       }
       public function strands()
       {
           return $this->belongsTo(tblstrand::class, 'strand_id');
       }
       public function section()
       {
           return $this->belongsTo(tblsection::class, 'section_id');
       }
}
