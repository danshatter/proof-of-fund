<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use App\Models\{Account, Role, User};
use App\Exceptions\{InvalidAccountNameException, AccountTakenException, AddAccountForbiddenException};
use Tests\TestCase;

class UpdateAccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_updating_account()
    {
        $individualAgentRole = Role::factory()
                                ->individualAgent()
                                ->create();
        $individualAgent = User::factory()
                            ->individualAgents()
                            ->emailVerified()
                            ->create();
        
        $this->actingAs($individualAgent, 'sanctum');
        $response = $this->postJson(route('accounts.store'));

        $response->assertInvalid(['account_number']);
        $response->assertUnprocessable();
    }

    /**
     * Account name not matching inputted name
     *
     * @return void
     */
    public function test_account_name_does_not_match_inputted_name()
    {
        $this->withoutExceptionHandling();
        $this->expectException(InvalidAccountNameException::class);
        $bankCode = '058';
        $accountNumber = '1100000000';
        Http::fake([
            "https://api.paystack.co/bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}" => Http::response([
                'status' => true,
                'message' => 'Account number resolved',
                'data' => [
                    'account_number' => $accountNumber,
                    'account_name' => 'Renners Investment',
                    'bank_id' => 9
                ]
            ], 200, [
                'Content-Type' => 'application/json'
            ]),
        ]);
        $individualAgentRole = Role::factory()
                                ->individualAgent()
                                ->create();
        $individualAgent = User::factory()
                            ->individualAgents()
                            ->emailVerified()
                            ->create();
        
        $this->actingAs($individualAgent, 'sanctum');
        $response = $this->postJson(route('accounts.store'), [
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'account_name' => 'Another account name'
        ]);
    }

    /**
     * Account has already been added by another user
     *
     * @return void
     */
    public function test_account_already_added_by_another_user()
    {
        $this->withoutExceptionHandling();
        $this->expectException(AccountTakenException::class);
        $bankCode = '058';
        $accountNumber = '1100000000';
        $accountName = 'Renners Investment';
        Http::fake([
            "https://api.paystack.co/bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}" => Http::response([
                'status' => true,
                'message' => 'Account number resolved',
                'data' => [
                    'account_number' => $accountNumber,
                    'account_name' => $accountName,
                    'bank_id' => 9
                ]
            ], 200, [
                'Content-Type' => 'application/json'
            ]),
        ]);
        $individualAgentRole = Role::factory()
                                ->individualAgent()
                                ->create();
        $individualAgent1 = User::factory()
                            ->individualAgents()
                            ->emailVerified()
                            ->create();
        $individualAgent2 = User::factory()
                            ->individualAgents()
                            ->emailVerified()
                            ->create();
        $account = Account::factory()
                        ->for($individualAgent2)
                        ->create([
                            'name' => $accountName,
                            'number' => $accountNumber,
                            'bank_code' => $bankCode
                        ]);
        
        $this->actingAs($individualAgent1, 'sanctum');
        $response = $this->postJson(route('accounts.store'), [
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'account_name' => $accountName
        ]);
    }

    /**
     * Account is forbidden to be added by user
     *
     * @return void
     */
    public function test_account_is_forbidden_to_be_added_by_user()
    {
        $this->withoutExceptionHandling();
        $this->expectException(AddAccountForbiddenException::class);
        $bankCode = '058';
        $accountNumber = '1100000000';
        $accountName = 'Renners Investment';
        $firstName = 'Oluyemi';
        $lastName = 'Adebayo';
        Http::fake([
            "https://api.paystack.co/bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}" => Http::response([
                'status' => true,
                'message' => 'Account number resolved',
                'data' => [
                    'account_number' => $accountNumber,
                    'account_name' => $accountName,
                    'bank_id' => 9
                ]
            ], 200, [
                'Content-Type' => 'application/json'
            ]),
        ]);
        $individualAgentRole = Role::factory()
                                ->individualAgent()
                                ->create();
        $individualAgent = User::factory()
                            ->individualAgents()
                            ->emailVerified()
                            ->create([
                                'first_name' => $firstName,
                                'last_name' => $lastName
                            ]);
        
        $this->actingAs($individualAgent, 'sanctum');
        $response = $this->postJson(route('accounts.store'), [
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'account_name' => $accountName
        ]);
    }

    /**
     * Account details updated successfully
     *
     * @return void
     */
    public function test_account_details_was_updated_successfully()
    {
        $bankCode = '058';
        $accountNumber = '1100000000';
        $firstName = 'Oluyemi';
        $lastName = 'Adebayo';
        $accountName = "{$firstName} {$lastName}";
        Http::fake([
            "https://api.paystack.co/bank/resolve?account_number={$accountNumber}&bank_code={$bankCode}" => Http::response([
                'status' => true,
                'message' => 'Account number resolved',
                'data' => [
                    'account_number' => $accountNumber,
                    'account_name' => $accountName,
                    'bank_id' => 9
                ]
            ], 200, [
                'Content-Type' => 'application/json'
            ]),
        ]);
        $individualAgentRole = Role::factory()
                                ->individualAgent()
                                ->create();
        $individualAgent = User::factory()
                            ->individualAgents()
                            ->emailVerified()
                            ->create([
                                'first_name' => $firstName,
                                'last_name' => $lastName
                            ]);
        
        $this->actingAs($individualAgent, 'sanctum');
        $response = $this->postJson(route('accounts.store'), [
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'account_name' => $accountName
        ]);

        Http::assertSent(fn(Request $request) => str_starts_with($request->url(), 'https://api.paystack.co/bank/resolve'));
        $response->assertOk();
        $this->assertDatabaseHas('accounts', [
            'user_id' => $individualAgent->id,
            'bank_code' => $bankCode,
            'number' => $accountNumber,
            'name' => $accountName
        ]);
    }
}
