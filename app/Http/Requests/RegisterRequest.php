<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Role;
use App\Rules\{Phone, PhoneUnique};

class RegisterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['required', new Phone, new PhoneUnique],
            'date_of_birth' => ['required', 'date'],
            'password' => ['required', 'string', 'min:5', 'confirmed'],
            'password_confirmation' => ['required'],
            'referral_code' => ['nullable', Rule::exists('users')->whereIn('role_id', [
                Role::INDIVIDUAL_AGENT,
                Role::AGENCY
            ])]
        ];
    }
}
