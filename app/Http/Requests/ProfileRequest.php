<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('profile'));
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:100', 'university' => 'nullable|string|max:150', 'department' => 'nullable|string|max:150', 'bio' => 'nullable|string|max:3000', 'availability' => 'nullable|in:available,part-time,unavailable'];
    }
}
