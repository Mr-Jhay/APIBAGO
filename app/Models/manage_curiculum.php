<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class manage_curiculum extends Model
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $table = 'manage_curiculum';

    protected $fillable = [
     
        'strand_id',
        'subject_id',
        'semester',
    ];

    public function subjects()
    {
        return $this->hasMany(tblsubject::class, 'id', 'subject_id');
    }

    public function strand()
    {
        return $this->belongsTo(tblstrand::class, 'strand_id');
    }


}
