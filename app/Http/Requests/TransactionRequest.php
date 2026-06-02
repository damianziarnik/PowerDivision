<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'between:-999999.99,999999.99',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ((float) $value === 0.0) {
                        $fail('The amount cannot be zero.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'The amount field is required.',
            'amount.numeric'  => 'The amount must be a number.',
            'amount.between'  => 'The amount must be between -999999.99 and 999999.99.',
        ];
    }
}

