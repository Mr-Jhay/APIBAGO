<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens; 
use Illuminate\Notifications\Notifiable;

class joinclass extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $table = 'joinclass';
    protected $fillable = [
        'user_id',  
        'class_id', 
        'status'                     
    ]; 
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
