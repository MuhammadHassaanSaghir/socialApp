<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        return [
            'User ID' => $this->user_id,
            'Title' => $this->title,
            'Body' => $this->body,
            'Attachment' => $this->attachment,
            'Privacy' => $this->privacy,
        ];
    }
}
