<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackOption extends Model
{
    use HasFactory;
    protected $table = 'addfeedbackchoices';
    protected $fillable = [
        'tblfeedback_id',
        'rating',
        'description',
    ];
}