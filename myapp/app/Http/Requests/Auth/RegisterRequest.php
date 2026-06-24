<?php

namespace App\Http\Requests\Auth;

use App\Services\UserService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:60',
            ],

            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                // Only lowercase letters, numbers, hyphens
                'regex:/^[a-z0-9-]+$/',
                // No leading or trailing hyphens
                'regex:/^(?!-)[a-z0-9-]*(?<!-)$/',
                'unique:users,username',
                // Custom rule: not in the reserved list
                function (string $attribute, mixed $value, \Closure $fail) {
                    $userService = app(UserService::class);
                    if ($userService->isUsernameReserved($value)) {
                        $fail('This username is reserved and cannot be used.');
                    }
                },
            ],

            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:254',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'max:72',
                // At least one uppercase letter
                'regex:/[A-Z]/',
                // At least one number
                'regex:/[0-9]/',
                'confirmed', // Expects password_confirmation field
            ],

            'locale' => [
                'sometimes',
                'string',
                'in:en,ar',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Your name is required.',
            'name.min'               => 'Name must be at least 2 characters.',
            'name.max'               => 'Name must be less than 60 characters.',

            'username.required'      => 'A username is required.',
            'username.min'           => 'Username must be at least 3 characters.',
            'username.max'           => 'Username must be less than 30 characters.',
            'username.regex'         => 'Username may only contain lowercase letters, numbers, and hyphens.',
            'username.unique'        => 'This username is already taken.',

            'email.required'         => 'An email address is required.',
            'email.email'            => 'Please enter a valid email address.',
            'email.unique'           => 'An account with this email already exists.',

            'password.required'      => 'A password is required.',
            'password.min'           => 'Password must be at least 8 characters.',
            'password.regex'         => 'Password must contain at least one uppercase letter and one number.',
            'password.confirmed'     => 'Password confirmation does not match.',
        ];
    }

    /**
     * Override failed validation to return our standard JSON envelope
     * instead of Laravel's default 422 response format.
     */
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

    /**
     * Normalize inputs before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => strtolower(trim($this->username ?? '')),
            'email'    => strtolower(trim($this->email ?? '')),
            'name'     => trim($this->name ?? ''),
        ]);
    }
}
