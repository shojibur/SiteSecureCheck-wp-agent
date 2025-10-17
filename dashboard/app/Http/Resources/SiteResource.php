<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'domain'=>$this->domain,
            'region_mode'=>$this->region_mode,
            'auto_fix'=>$this->auto_fix,
            'last_score'=>$this->last_score,
            'teams_webhook'=>$this->teams_webhook,
            'email'=>$this->email,
            'created_at'=>$this->created_at,
        ];
    }
}

