<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('profile'));
    }

    public function rules(): array
    {
        return ['skill_id' => ['required', 'integer', 'exists:skills,id', Rule::unique('student_skills')->where('student_profile_id', $this->route('profile')->id)->ignore($this->route('studentSkill')?->id)], 'proficiency' => 'required|in:beginner,intermediate,advanced'];
    }
}
