<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class tblclassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'teacher_postion'=>$this->teacher_postion,
            'strand_id'=>$this->strand_id,
            'section_id'=>$this->section_id,
            'subject_id'=>$this->subject_id,
            'class_desc'=>$this->class_desc,
            'profile_img'=>$this->profile_img,
            'gen_code'=>$this->gen_code,
            'created_at'=>$this->created_at,
            'updated_at' => $this->updated_at,
            ];
    }
}
