<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        return ['document' => ['bail', 'required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120', function ($attribute, $file, $fail) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname());
            if (! in_array($mime, ['image/jpeg', 'image/png', 'application/pdf']) || ($mime !== 'application/pdf' && @getimagesize($file->getPathname()) === false)) {
                $fail('The document must contain a valid JPEG, PNG or PDF.');
            }
        }]];
    }
}
