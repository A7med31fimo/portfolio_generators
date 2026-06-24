<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'string', 'email', 'exists:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:72',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'        => 'Reset token is required.',
            'email.required'        => 'Email address is required.',
            'email.exists'          => 'No account found with this email address.',
            'password.required'     => 'New password is required.',
            'password.min'          => 'Password must be at least 8 characters.',
            'password.regex'        => 'Password must contain at least one uppercase letter and one number.',
            'password.confirmed'    => 'Password confirmation does not match.',
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
        $this->merge(['email' => strtolower(trim($this->email ?? ''))]);
    }
}
