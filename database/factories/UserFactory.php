<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'remember_token' => Str::random(10),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model's account should be verified.
     *
     * @return static
     */
    public function verified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'verified_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the model's email address should be verified.
     *
     * @return static
     */
    public function emailVerified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => now(),
            ];
        });
    }

    /**
     * Fill data for users
     *
     * @return static
     */
    public function users(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role_id' => Role::USER,
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'date_of_birth' => fake()->date(),
            ];
        });
    }

    /**
     * Fill data for individual agents
     *
     * @return static
     */
    public function individualAgents(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role_id' => Role::INDIVIDUAL_AGENT,
                'date_of_birth' => fake()->date(),
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address' => fake()->address(),
                'request_message' => fake()->paragraph()
            ];
        });
    }

    /**
     * Fill data for agencies
     *
     * @return static
     */
    public function agencies(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role_id' => Role::AGENCY,
                'business_name' => fake()->company(),
                'business_website' => fake()->url(),
                'business_state' => fake()->city(),
                'address' => fake()->address(),
                'request_message' => fake()->paragraph()
            ];
        });
    }

    /**
     * Fill data for administrators
     *
     * @return static
     */
    public function administrators(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role_id' => Role::ADMINISTRATOR,
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email_verified_at' => now()
            ];
        });
    }
}
