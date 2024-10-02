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
       // 'user_id',  
       // 'strand_id',  
       // 'section_id',  
       // 'subject_id',  
       // 'scuriculum_id', // Added curriculum ID
       // 'class_desc',  
      //  'profile_img',  
      //  'gen_code',
       // 'semester', // Added semester
       // 'year',     // Added year
 
        'curiculum_id',
        'strand_id',
        'section_id',
        'subject_id',
        'year_id',
        'semester',
        'class_desc',
        'profile_img',
        'gen_code',
        'user_id',

        
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function strand()
    {
        return $this->belongsTo(tblstrand::class, 'strand_id');
    }

    public function subject()
    {
        return $this->belongsTo(tblsubject::class, 'subject_id');
    }

    public function curriculum()
    {
        return $this->belongsTo(strandcuriculum::class, 'curiculum_id');
    }



    public function section()
    {
        return $this->belongsTo(tblsection::class, 'section_id');
    }





    public function year()
    {
        return $this->belongsTo(tblyear::class, 'year_id');
    }
}
