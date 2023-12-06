<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Aws\Laravel\AwsFacade;
use App\Models\{Camouflage, Role, User};
use App\Exceptions\{BvnImageAlreadyVerifiedException, InsufficientFaceMatchException, MultipleFacesDetectedException, UnmatchingFacesException};
use Tests\TestCase;

class ConfirmBvnImageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Validation errors
     *
     * @return void
     */
    public function test_validation_errors_occur_while_confirming_bvn_image()
    {
        $phone = '+2348123456789';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([            
                        'phone' => $phone
                    ]);

        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn-image'), [
            'image' => null
        ]);

        $response->assertInvalid(['image']);
        $response->assertUnprocessable();
    }

    /**
     * BVN image already verified
     */
    public function test_bvn_image_is_already_verified()
    {
        $image = UploadedFile::fake()->create('image.png', 1024);
        $this->withoutExceptionHandling();
        $this->expectException(BvnImageAlreadyVerifiedException::class);
        $phone = '+2348123456789';
        $userRole = Role::factory()
                        ->user()
                        ->create();
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
                                    'phone' => $phone,
                                ]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn-image'), [
            'image' => $image
        ]);
    }

    /**
     * BVN response has unmatching faces
     */
    public function test_bvn_image_has_unmatching_faces()
    {
        $image = UploadedFile::fake()->create('image.png', 1024);
        $this->withoutExceptionHandling();
        $this->expectException(UnmatchingFacesException::class);
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
                                ->create([
                                    'phone' => $phone
                                ]);
        AwsFacade::shouldReceive('createClient->compareFaces->get')
                ->once()
                ->andReturn([]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn-image'), [
            'image' => $image
        ]);
    }

    /**
     * BVN response has multiple faces
     */
    public function test_bvn_image_has_multiple_faces()
    {
        $image = UploadedFile::fake()->create('image.png', 1024);
        $this->withoutExceptionHandling();
        $this->expectException(MultipleFacesDetectedException::class);
        $phone = '+2348123456789';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone
                    ]);
        $bvn = '222222222222';
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->create([
                                    'phone' => $phone
                                ]);
        AwsFacade::shouldReceive('createClient->compareFaces->get')
                ->once()
                ->andReturn([
                    ['Similarity' => config('japa.face_match_minimum_percentage') - 4],
                    ['Similarity' => config('japa.face_match_minimum_percentage') - 6],
                ]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn-image'), [
            'image' => $image
        ]);
    }

    /**
     * BVN response insufficient matching
     */
    public function test_bvn_image_does_not_match_sufficiently()
    {
        $image = UploadedFile::fake()->create('image.png', 1024);
        $this->withoutExceptionHandling();
        $this->expectException(InsufficientFaceMatchException::class);
        $phone = '+2348123456789';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone
                    ]);
        $bvn = '222222222222';
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->create([
                                    'phone' => $phone
                                ]);
        AwsFacade::shouldReceive('createClient->compareFaces->get')
                ->once()
                ->andReturn([
                    ['Similarity' => config('japa.face_match_minimum_percentage') - 5],
                ]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn-image'), [
            'image' => $image
        ]);
    }

    /**
     * BVN image verification successful
     */
    public function test_bvn_image_verification_successful()
    {
        $image = UploadedFile::fake()->create('image.png', 1024);
        $phone = '+2348123456789';
        $userRole = Role::factory()
                        ->user()
                        ->create();
        $user = User::factory()
                    ->users()
                    ->verified()
                    ->create([
                        'phone' => $phone
                    ]);
        $bvn = '222222222222';
        $camouflage = Camouflage::factory()
                                ->for($user)
                                ->create([
                                    'phone' => $phone
                                ]);
        AwsFacade::shouldReceive('createClient->compareFaces->get')
                ->once()
                ->andReturn([
                    ['Similarity' => 99.999],
                ]);
        
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson(route('auth.confirm-bvn-image'), [
            'image' => $image
        ]);
        $camouflage->refresh();

        $this->assertNotNull($camouflage->image_verified_at);
    }
}
