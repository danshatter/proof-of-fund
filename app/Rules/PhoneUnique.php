<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;
use App\Services\Phone\Nigeria as NigerianPhone;

class PhoneUnique implements ValidationRule
{
    /**
     * The ID of the user to exclude
     */
    private $userId;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = 'The :attribute has already been taken.';

        if (isset($this->userId)) {
            if (User::where('phone', app()->make(NigerianPhone::class)->convert($value))
                    ->where('id', '!=', $this->userId)
                    ->exists()) {
                $fail($message);
            }
        }

        if (User::where('phone', app()->make(NigerianPhone::class)->convert($value))
                ->exists()) {
            $fail($message);
        }
    }
}
