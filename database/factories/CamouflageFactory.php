<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Camouflage>
 */
class CamouflageFactory extends Factory
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
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->e164PhoneNumber(),
            'gender' => fake()->randomElement([
                'MALE',
                'FEMALE'
            ]),
            'date_of_birth' => now()->subYears(20)->format('Y-m-d'),
            'image' => 'base64-image',
            'confidential' => '10000000001',
            'confidential_hash' => fn($attributes) => hash_hmac('sha256', $attributes['confidential'], config('japa.bvn_hash_secret')),
            'nationality' => 'Nigerian'
        ];
    }

    /**
     * Verify the BVN
     *
     * @return static
     */
    public function verified()
    {
        return $this->state(function (array $attributes) {
            return [
                'verified_at' => now(),
                'image_verified_at' => now()
            ];
        });
    }
}
