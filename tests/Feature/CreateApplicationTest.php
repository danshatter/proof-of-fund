<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Models\{Application, Camouflage, Option, Role, Tenure, User};
use App\Exceptions\OngoingApplicationException;
use Tests\TestCase;

class CreateApplicationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_creating_application()
    {
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('applications.store'), [
            'option_id' => null
        ]);

        $response->assertInvalid(['option_id']);
        $response->assertUnprocessable();
    }

    /**
     * User has an ongoing loan
     */
    public function test_user_has_an_ongoing_loan()
    {
        $this->withoutExceptionHandling();
        $this->expectException(OngoingApplicationException::class);
        $proofOfResidenceImage = UploadedFile::fake()->create('image.png', 1024);
        $internationalPassportImage = UploadedFile::fake()->create('image.png', 1024);
        $amount = 1000000;
        $userRole = Role::factory()
                        ->user()
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
                                ->create();
        $option = Option::factory()
                        ->create();
        $tenure = Tenure::factory()
                        ->create();

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('applications.store'), [
            'option_id' => $option->id,
            'tenure_id' => $tenure->id,
            'amount' => $amount,
            'state_of_origin' => fake()->city(),
            'residential_address' => fake()->address(),
            'state_of_residence' => fake()->city(),
            'proof_of_residence_image' => $proofOfResidenceImage,
            'travel_purpose' => fake()->word(),
            'travel_destination' => fake()->country(),
            'international_passport_number' => Str::random(15),
            'international_passport_expiry_date' => fake()->date(),
            'international_passport_image' => $internationalPassportImage,
            'guarantor_first_name' => fake()->firstName(),
            'guarantor_last_name' => fake()->lastName(),
            'guarantor_phone' => fake()->e164PhoneNumber(),
            'guarantor_email' => fake()->safeEmail(),
            'travel_sponsor_first_name' => fake()->firstName(),
            'travel_sponsor_last_name' => fake()->lastName(),
            'travel_sponsor_phone' => fake()->e164PhoneNumber(),
            'travel_sponsor_email' => fake()->safeEmail()
        ]);
    }

    /**
     * Application was created successfully
     */
    public function test_application_was_successfully_created()
    {
        $proofOfResidenceImage = UploadedFile::fake()->create('image.png', 1024);
        $internationalPassportImage = UploadedFile::fake()->create('image.png', 1024);
        $amount = 1000000;
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create();
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->verified()
                                ->create();
        $option = Option::factory()
                        ->create();
        $tenure = Tenure::factory()
                        ->create();

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('applications.store'), [
            'option_id' => $option->id,
            'tenure_id' => $tenure->id,
            'amount' => $amount,
            'state_of_origin' => fake()->city(),
            'residential_address' => fake()->address(),
            'state_of_residence' => fake()->city(),
            'proof_of_residence_image' => $proofOfResidenceImage,
            'travel_purpose' => fake()->word(),
            'travel_destination' => fake()->country(),
            'international_passport_number' => Str::random(15),
            'international_passport_expiry_date' => fake()->date(),
            'international_passport_image' => $internationalPassportImage,
            'guarantor_first_name' => fake()->firstName(),
            'guarantor_last_name' => fake()->lastName(),
            'guarantor_phone' => fake()->e164PhoneNumber(),
            'guarantor_email' => fake()->safeEmail(),
            'travel_sponsor_first_name' => fake()->firstName(),
            'travel_sponsor_last_name' => fake()->lastName(),
            'travel_sponsor_phone' => fake()->e164PhoneNumber(),
            'travel_sponsor_email' => fake()->safeEmail()
        ]);

        $response->assertValid();
        $response->assertCreated();
        $this->assertDatabaseHas('applications', [
            'user_id' => $user->id,
            'amount' => $amount
        ]);
        Storage::disk(config('filesystems.default'))->assertExists('proof-of-residences/'.$proofOfResidenceImage->hashName());
        Storage::disk(config('filesystems.default'))->assertExists('international-passports/'.$internationalPassportImage->hashName());
    }
}
