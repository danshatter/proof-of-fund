<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Phone;

class StoreApplicationRequest extends FormRequest
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
            'option_id' => ['required', 'integer', 'exists:options,id'],
            'tenure_id' => ['required', 'integer', 'exists:tenures,id'],
            'amount' => ['required', 'integer', 'min:100'],
            'state_of_origin' => ['required'],
            'residential_address' => ['required'],
            'state_of_residence' => ['required'],
            'proof_of_residence_image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:1024'],
            'travel_purpose' => ['required'],
            'travel_destination' => ['required'],
            'international_passport_number' => ['required'],
            'international_passport_expiry_date' => ['required', 'date'],
            'international_passport_image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:1024'],
            'guarantor_first_name' => ['required'],
            'guarantor_last_name' => ['required'],
            'guarantor_phone' => ['required', new Phone],
            'guarantor_email' => ['nullable', 'email'],
            'travel_sponsor_first_name' => ['nullable', 'required_with:travel_sponsor_last_name,travel_sponsor_phone,travel_sponsor_email'],
            'travel_sponsor_last_name' => ['nullable', 'required_with:travel_sponsor_first_name,travel_sponsor_phone,travel_sponsor_email'],
            'travel_sponsor_phone' => ['nullable', 'required_with:travel_sponsor_first_name,travel_sponsor_last_name,travel_sponsor_email', new Phone],
            'travel_sponsor_email' => ['nullable', 'email']
        ];
    }
}
