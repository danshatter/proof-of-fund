<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\{Application, User};

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => 100000000,
            'amount_remaining' => 100000000,
            'tenure' => '1 MONTH',
            'type' => fake()->sentence(),
            'interest' => fake()->numberBetween(0, 20),
            'state_of_origin' => fake()->city(),
            'residential_address' => fake()->address(),
            'state_of_residence' => fake()->city(),
            'travel_purpose' => fake()->word(),
            'travel_destination' => fake()->country(),
            'proof_of_residence_image' => fake()->url(),
            'proof_of_residence_image_url' => fake()->url(),
            'proof_of_residence_image_driver' => config('filesystems.default'),
            'international_passport_number' => Str::random(12),
            'international_passport_expiry_date' => fake()->date(),
            'international_passport_image' => fake()->url(),
            'international_passport_image_url' => fake()->url(),
            'international_passport_image_driver' => config('filesystems.default'),
            'guarantor' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone' => fake()->e164PhoneNumber(),
                'email' => fake()->safeEmail()
            ],
            'travel_sponsor' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone' => fake()->e164PhoneNumber(),
                'email' => fake()->safeEmail()
            ],
            'details' => [
                [
                    'loan_number' => 1,
                    'amount' => 100000000,
                    'payment_date' => now()->addMonth()->format('Y-m-d'),
                    'amount_remaining' => 100000000,
                    'status' => Application::INSTALLMENT_PENDING,
                    'closed_at' => null
                ]
            ]
        ];
    }
}
