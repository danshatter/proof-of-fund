<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use App\Models\{Application, Camouflage, Role, User};
use App\Exceptions\InternationalPassportNoMatchException;
use Tests\TestCase;

class GetApplicationPassportDetailsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Application not found
     */
    public function test_application_is_not_found()
    {
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();

        Sanctum::actingAs($admin, ['*']);
        $response = $this->getJson(route('admin.applications.passport-details', [
            'application' => 'non-existent-id'
        ]));

        $response->assertNotFound();
    }

    /**
     * Passport details could not be found
     */
    public function test_passport_details_could_not_be_found()
    {
        $this->withoutExceptionHandling();
        $this->expectException(InternationalPassportNoMatchException::class);
        $internationalPassportNumber = 'A10000001';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();
        $application = Application::factory()
                                ->for($user)
                                ->create([
                                    'international_passport_number' => $internationalPassportNumber
                                ]);
        Http::fake([
            'https://api.qoreid.com/token' => Http::response([
                'accessToken' => 'access-token',
                'expiresIn' => 7200,
                'tokenType' => 'Bearer'
            ], 200, [
                'Content-Type' => 'application/json'
            ]),

            "https://api.qoreid.com/v1/ng/identities/passport/{$internationalPassportNumber}" => [
                'id' => 1498761,
                'applicant' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                ],
                'summary' => [
                    'passport_ng_check' => [
                        'status' => 'NO_MATCH',
                        'fieldMatches' => [
                            'firstname' => false,
                            'lastname' => false,
                        ],
                    ],
                ],
                'status' => [
                    'state' => 'complete',
                    'status' => 'id_mismatch',
                ],
            ], 200, [
                'Content-Type' => 'application/json'
            ]
        ]);

        Sanctum::actingAs($admin, ['*']);
        $response = $this->getJson(route('admin.applications.passport-details', [
            'application' => $application
        ]));
    }

    /**
     * Application status successfully updated
     */
    public function test_passport_details_were_successfully_fetched()
    {
        $internationalPassportNumber = 'A10000001';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $adminRole = Role::factory()
                        ->administrator()
                        ->create();
        $admin = User::factory()
                    ->administrators()
                    ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();
        $application = Application::factory()
                                ->for($user)
                                ->create([
                                    'international_passport_number' => $internationalPassportNumber
                                ]);
        Http::fake([
            'https://api.qoreid.com/token' => Http::response([
                'accessToken' => 'access-token',
                'expiresIn' => 7200,
                'tokenType' => 'Bearer'
            ], 200, [
                'Content-Type' => 'application/json'
            ]),

            "https://api.qoreid.com/v1/ng/identities/passport/{$internationalPassportNumber}" => [
                'id' => 1498761,
                'applicant' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                ],
                'summary' => [
                    'passport_ng_check' => [
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
                'passport_ng' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'middlename' => 'Bunch',
                    'birthdate' => '1960-10-01',
                    'photo' => 'base-64-encoded-image',
                    'gender' => 'male',
                    'issuedAt' => 'OSOGBO',
                    'issuedDate' => '1960-10-01',
                    'expiryDate' => '1960-10-01',
                    'passportNo' => $internationalPassportNumber
                ],
            ], 200, [
                'Content-Type' => 'application/json'
            ]
        ]);

        Sanctum::actingAs($admin, ['*']);
        $response = $this->getJson(route('admin.applications.passport-details', [
            'application' => $application
        ]));

        $response->assertOk();
    }
}
