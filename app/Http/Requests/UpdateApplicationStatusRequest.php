<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Application;

class UpdateApplicationStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                Application::PENDING,
                Application::IN_REVIEW,
                Application::REJECTED,
                Application::ACCEPTED,
                Application::OPEN,
                Application::COMPLETED
            ])]
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $status = $this->input('status');

        if (is_string($status)) {
            $this->merge([
                'status' => strtoupper($status)
            ]);
        }
    }
}
