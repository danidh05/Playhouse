<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaySessionRequest extends FormRequest
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
        $paymentMethods = config('play.payment_methods', []);
        
        return [
            'child_id' => 'required|exists:children,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'start_time' => ['nullable', 'date', 'before_or_equal:now'],
            'discount_pct' => 'nullable|numeric|min:0|max:100',
            'payment_method' => ['nullable', Rule::in($paymentMethods)],
            'planned_hours' => 'nullable|numeric|min:0.1',
            'notes' => 'nullable|string',
        ];
    }
} 