<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\{Phone, PhoneUnique};

class AgentRegisterRequest extends FormRequest
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
            'register_as' => ['required', Rule::in([
                'INDIVIDUAL',
                'AGENCY'
            ])],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['required', new Phone, new PhoneUnique],
            'address' => ['required'],
            'request_message' => ['required'],
            'password' => ['required', 'string', 'min:5', 'confirmed'],
            'password_confirmation' => ['required'],
            'first_name' => ['nullable', 'required_if:register_as,INDIVIDUAL'],
            'last_name' => ['nullable', 'required_if:register_as,INDIVIDUAL'],
            'date_of_birth' => ['nullable', 'required_if:register_as,INDIVIDUAL', 'date'],
            'business_name' => ['nullable', 'required_if:register_as,AGENCY', 'unique:users'],
            'business_website' => ['nullable', 'required_if:register_as,AGENCY', 'url'],
            'business_state' => ['nullable', 'required_if:register_as,AGENCY'],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $registerAs = $this->input('register_as');

        if (is_string($registerAs)) {
            $this->merge([
                'register_as' => strtoupper($registerAs)
            ]);
        }
    }
}
