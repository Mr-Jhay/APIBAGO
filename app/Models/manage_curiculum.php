<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens; 
use Illuminate\Notifications\Notifiable;

class manage_curiculum extends Model
{ 
    use HasFactory, Notifiable, HasApiTokens;
    protected $table = 'manage_curiculum';

    protected $fillable = [
           
           'scuriculum_id',
           'subject_id',  
           'strand_id',             
           'semester',            
       ]; 

       public function strand()
       {
           return $this->belongsTo(tblstrand::class, 'strand_id');
       }
       public function subject()
       {
           return $this->belongsTo(tblsubject::class, 'subject_id');
       }

       public function strandcuriculum()
       {
        return $this->belongsTo(strandcuriculum::class,'scuriculum_id');
       }


}
