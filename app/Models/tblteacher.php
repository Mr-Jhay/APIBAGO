<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class tblteacher extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'tblteacher';

    protected $fillable = [
           'user_id',
           'position_id',         
       ];
       
    public function strands()
    {
        return $this->belongsToMany(Strand::class, 'teacher_strand', 'teacher_id', 'strand_id');
    }
}
