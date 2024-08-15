<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'id' => $this->id,
             'idnumber' => $this->idnumber,
             'fname' => $this->fname,
            // 'mname' => $this->mname,
            // 'lname' => $this->lname,
             'sex' => $this->sex,
             'email' => $this->email,
             'usertype' => $this->usertype,
            // 'posts'=> tblteacherResource::collection($this->tblteacher),
            // 'posts2'=> tblstudentResource::collection($this->tblstudent),
            // 'Mobile_no' => $this->Mobile_no,
             'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
         ];
    }
}
