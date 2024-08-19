<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class tblstrandResource extends JsonResource
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
            'addstrand'=>$this->addstrand,
            'grade_level'=>$this->grade_level,
            'created_at'=>$this->created_at,
            'updated_at' => $this->updated_at,
            ];
    }
}
