<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\{Transaction, Application};

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'amount' => fake()->numberBetween(1000000000, 2000000000),
            'reference' => Str::random(10),
            'transfer_code' => Str::random(12),
            'recipient_code' => Str::random(13),
            'type' => fake()->randomElement([
                Transaction::DEBIT,
                Transaction::PAYMENT,
                Transaction::REFUND
            ]),
            'currency' => config('japa.currency_code')
        ];
    }
}
