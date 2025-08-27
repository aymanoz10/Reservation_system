<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RestaurantResource extends JsonResource
{
    public function toArray($request)
    {
        $imageUrl = $this->image ? asset(Storage::url($this->image)) : null;

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'ar_title' => $this->ar_title,
            'en_title' => $this->en_title,
            'location' => $this->location,
            'image' => $imageUrl,
            'is_closed' => $this->is_closed,
            'closed_from' => $this->closed_from,
            'closed_until' => $this->closed_until,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
