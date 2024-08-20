<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class teacher_strandResource extends JsonResource
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
            
            'teacher_id'=>$this->teacher_id,
            'strand_id'=>$this->strand_id,
            'created_at'=>$this->created_at,
            'updated_at' => $this->updated_at,
            ];
    }
}
