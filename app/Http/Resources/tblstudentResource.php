<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class tblstudentResource extends JsonResource
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
            'user_id'=>$this->user_id,
            'strand_id'=>$this->strand_id,
            'section_id'=>$this->section_id,
            'Mobile_no'=>$this->Mobile_no,
            'created_at'=>$this->created_at,
            'updated_at' => $this->updated_at,
            ];
    }
}
