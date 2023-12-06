<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Card>
 */
class CardFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'authorization' => [
                'bin' => '408408',
                'bank' => 'TEST BANK',
                'brand' => 'visa',
                'last4' => '4081',
                'channel' => 'card',
                'exp_year' => '2023',
                'reusable' => true,
                'card_type' => 'visa',
                'exp_month' => '02',
                'signature' => 'SIG_kurmAinAZd68s3WUbMzm',
                'account_name' => null,
                'country_code' => 'NG',
                'receiver_bank' => null,
                'authorization_code' => 'AUTH_zz1f6j5qr8',
                'receiver_bank_account_number' => null
            ],
            'signature' => fn($attributes) => $attributes['authorization']['signature']
        ];
    }
}
