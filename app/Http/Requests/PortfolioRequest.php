<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('profile') ?? $this->route('portfolioItem'));
    }

    public function rules(): array
    {
        return ['title' => 'required|string|max:150', 'description' => 'required|string|max:3000', 'url' => 'required|url:http,https|max:2048'];
    }
}
