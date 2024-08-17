<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class teacher_strand extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'tblteacher';

    protected $fillable = [
           'teacher_id',
           'strand_id',         
       ];

    public function teachers()
    {
        return $this->belongsToMany(tblteacher::class, 'teacher_strand', 'strand_id', 'teacher_id');
    }
}
