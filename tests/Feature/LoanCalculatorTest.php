<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{Option, Tenure};

class LoanCalculatorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_calculating_loan()
    {
        $response = $this->getJson(route('loan-calculator', [
            'option_id' => null
        ]));

        $response->assertInvalid(['option_id']);
        $response->assertUnprocessable();
    }

    /**
     * Loan details was successfully fetched
     */
    public function test_loan_details_was_successfully_fetched()
    {
        $option = Option::factory()
                        ->create();
        $tenure = Tenure::factory()
                        ->create();
        $amount = 2000000;

        $response = $this->getJson(route('loan-calculator', [
            'option_id' => $option->id,
            'tenure_id' => $tenure->id,
            'amount' => $amount
        ]));

        $response->assertOk();
    }
}
