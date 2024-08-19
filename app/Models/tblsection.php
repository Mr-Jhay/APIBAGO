<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class tblsection extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $table = 'tblsection';

    protected $fillable = [
        'strand_id',
           'section',         
       ];

       public function strand()
       {
           return $this->belongsTo(Tblstrand::class, 'strand_id');
       }
}
