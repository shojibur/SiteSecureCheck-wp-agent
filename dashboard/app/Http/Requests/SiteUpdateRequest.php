<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiteUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name' => ['nullable','string','max:255'],
            'domain' => ['required','url'],
            'wp_api_base' => ['required','url'],
            'wp_api_key' => ['required','string'],
            'region_mode' => ['required','in:EU,MY,US,OTHER'],
            'auto_fix' => ['boolean'],
            'teams_webhook' => ['nullable','url'],
            'email' => ['nullable','email']
        ];
    }
}

