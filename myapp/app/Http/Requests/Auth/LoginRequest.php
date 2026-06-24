<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'string', 'email'],
            'password'    => ['required', 'string'],
            'remember'    => ['sometimes', 'boolean'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email is required.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'       => strtolower(trim($this->email ?? '')),
            'device_name' => $this->device_name ?? $this->userAgent() ?? 'web',
        ]);
    }
}
