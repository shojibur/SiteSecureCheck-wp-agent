<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'=>$this->id,
            'score'=>$this->score,
            'status'=>$this->status,
            'applied'=>$this->applied,
            'issues'=>$this->issues,
            'plan'=>$this->plan,
            'created_at'=>$this->created_at,
        ];
    }
}

