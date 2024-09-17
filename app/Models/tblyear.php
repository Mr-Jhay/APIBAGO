<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Schema;

class tblyear extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'tblyear';

    // Add 'is_active' to fillable fields
    protected $fillable = [
        'addyear',
        'is_active', // Include is_active field for mass assignment
    ];


    public function down(): void
    {
        Schema::dropIfExists('tblyear');
    }
};


