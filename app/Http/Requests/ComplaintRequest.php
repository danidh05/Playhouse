<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $types = config('play.complaint_types', []);
        
        return [
            'shift_id' => ['required', 'exists:shifts,id'],
            'child_id' => ['nullable', 'exists:children,id'],
            'type' => ['required', 'string', Rule::in($types)],
            'description' => ['required', 'string'],
        ];
    }
} 