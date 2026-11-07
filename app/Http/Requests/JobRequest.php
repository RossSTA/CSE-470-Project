<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'client' && (! $this->route('job') || $this->user()->can('update', $this->route('job')));
    }

    public function rules(): array
    {
        return ['title' => 'required|string|max:150', 'description' => 'required|string|max:10000', 'type' => 'required|in:gig,internship', 'budget' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999.99', 'deadline' => 'required|date|after:today', 'skills' => 'required|array|min:1|max:30', 'skills.*' => 'required|integer|distinct|exists:skills,id', 'screening_questions' => 'nullable|array|max:10', 'screening_questions.*' => 'required|string|max:500'];
    }
}
