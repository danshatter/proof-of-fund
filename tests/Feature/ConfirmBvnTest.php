<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Notification};
use App\Models\{Camouflage, Role, User};
use App\Exceptions\{BvnAlreadyVerifiedException, BvnLinkedToExistingAccountException, BvnNoMatchException};
use App\Notifications\BvnVerificationNotification;
use Tests\TestCase;

class ConfirmBvnTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_confirming_bvn()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([            
                        'phone' => $phone
                    ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn'), [
            'bvn' => null
        ]);

        $response->assertInvalid(['bvn']);
        $response->assertUnprocessable();
    }

    /**
     * BVN already verified
     */
    public function test_bvn_is_already_verified()
    {
        $this->withoutExceptionHandling();
        $this->expectException(BvnAlreadyVerifiedException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone
                    ]);
        $bvn = '222222222222';
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create([
                                    'phone' => $phone
                                ]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn'), [
            'bvn' => $bvn
        ]);
    }

    /**
     * BVN linked to existing account
     */
    public function test_bvn_is_linked_to_an_existing_account()
    {
        $this->withoutExceptionHandling();
        $this->expectException(BvnLinkedToExistingAccountException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone1 = '+2348123456789';
        $phone2 = '+2348123456700';
        $user1 = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone1
                    ]);
        $user2 = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone2
                    ]);
        $bvn = '222222222222';
        $camouflage = Camouflage::factory()
                                ->for($user2)
                                ->verified()
                                ->create([
                                    'phone' => $phone1,
                                    'confidential' => $bvn,
                                ]);
        
        $this->actingAs($user1, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn'), [
            'bvn' => $bvn
        ]);
    }

    /**
     * No matching for BVN details
     */
    public function test_bvn_details_has_no_match()
    {
        $this->withoutExceptionHandling();
        $this->expectException(BvnNoMatchException::class);
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone
                    ]);
        $bvn = '222222222222';
        Notification::fake();
        Http::fake([
            'https://api.qoreid.com/token' => Http::response([
                'accessToken' => 'access-token',
                'expiresIn' => 7200,
                'tokenType' => 'Bearer'
            ], 200, [
                'Content-Type' => 'application/json'
            ]),

            "https://api.qoreid.com/v1/ng/identities/bvn-basic/{$bvn}" => Http::response([
                'id' => 130309,
                'applicant' => [
                    'firstname' => 'chigozirim',
                    'lastname' => 'isikaku',
                ],
                'summary' => [
                    'bvn_check' => [
                        'status' => 'NO_MATCH',
                        'fieldMatches' => [
                            'firstname' => false,
                            'lastname' => true,
                        ],
                    ],
                ],
                'status' => [
                    'state' => 'complete',
                    'status' => 'id_mismatch',
                ],
            ], 200, [
                'Content-Type' => 'application/json'
            ])
        ]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn'), [
            'bvn' => $bvn
        ]);
    }

    /**
     * BVN confirmation successful and verified
     */
    public function test_bvn_confirmation_was_successful_and_bvn_was_verified()
    {
        $this->withoutExceptionHandling();
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $phone = '+2348123456789';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone,
                    ]);
        $bvn = '222222222222';
        Notification::fake();
        Http::fake([
            'https://api.qoreid.com/token' => Http::response([
                'accessToken' => 'access-token',
                'expiresIn' => 7200,
                'tokenType' => 'Bearer'
            ], 200, [
                'Content-Type' => 'application/json'
            ]),

            "https://api.qoreid.com/v1/ng/identities/bvn-basic/{$bvn}" => [
                'id' => 130459,
                'applicant' => [
                    'firstname' => 'daniel',
                    'lastname' => 'isikaku',
                ],
                'summary' => [
                    'bvn_check' => [
                        'status' => 'EXACT_MATCH',
                        'fieldMatches' => [
                            'firstname' => true,
                            'lastname' => true,
                        ],
                    ],
                ],
                'status' => [
                    'state' => 'complete',
                    'status' => 'verified',
                ],
                'bvn' => [
                    'bvn' => '22218472554',
                    'firstname' => 'DANIEL',
                    'lastname' => 'ISIKAKU',
                    'middlename' => 'CHIGOZIRIM',
                    'birthdate' => '26-03-1995',
                    'gender' => 'Male',
                    'phone' => $user->phone,
                    'photo' => 'base64-image',
                ],
            ], 200, [
                'Content-Type' => 'application/json'
            ]
        ]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn'), [
            'bvn' => $bvn
        ]);

        $response->assertValid();
        $response->assertOk();
        $this->assertDatabaseHas('camouflages', [
            'user_id' => $user->id
        ]);
        Notification::assertNotSentTo($user, BvnVerificationNotification::class);
    }

    /**
     * BVN confirmation successful and awaiting verification
     */
    public function test_bvn_confirmation_was_initiated_successfully_and_awaiting_verification()
    {
        $this->withoutExceptionHandling();
        $userRole = Role::factory()->user()->create();
        $phone = '+2348123456789';
        $bvnPhone = '+2348123456700';
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone,
                    ]);
        $bvn = '222222222222';
        Notification::fake();
        Http::fake([
            'https://api.qoreid.com/token' => Http::response([
                'accessToken' => 'access-token',
                'expiresIn' => 7200,
                'tokenType' => 'Bearer'
            ], 200, [
                'Content-Type' => 'application/json'
            ]),

            "https://api.qoreid.com/v1/ng/identities/bvn-basic/{$bvn}" => [
                'id' => 130459,
                'applicant' => [
                    'firstname' => 'daniel',
                    'lastname' => 'isikaku',
                ],
                'summary' => [
                    'bvn_check' => [
                        'status' => 'EXACT_MATCH',
                        'fieldMatches' => [
                            'firstname' => true,
                            'lastname' => true,
                        ],
                    ],
                ],
                'status' => [
                    'state' => 'complete',
                    'status' => 'verified',
                ],
                'bvn' => [
                    'bvn' => '22218472554',
                    'firstname' => 'DANIEL',
                    'lastname' => 'ISIKAKU',
                    'middlename' => 'CHIGOZIRIM',
                    'birthdate' => '26-03-1995',
                    'gender' => 'Male',
                    'phone' => $bvnPhone,
                    'photo' => 'base64-image',
                ],
            ], 200, [
                'Content-Type' => 'application/json'
            ]
        ]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn'), [
            'bvn' => $bvn
        ]);

        $response->assertValid();
        $response->assertOk();
        $this->assertDatabaseHas('camouflages', [
            'user_id' => $user->id
        ]);
        Notification::assertSentTo($user, BvnVerificationNotification::class);
    }
}
