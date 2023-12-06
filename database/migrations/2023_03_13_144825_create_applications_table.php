<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('amount');
            $table->bigInteger('amount_remaining')->index();
            $table->string('tenure');
            $table->string('type');
            $table->string('interest');
            $table->string('state_of_origin');
            $table->string('residential_address');
            $table->string('state_of_residence');
            $table->string('proof_of_residence_image');
            $table->string('proof_of_residence_image_url');
            $table->string('proof_of_residence_image_driver');
            $table->string('travel_purpose');
            $table->string('travel_destination');
            $table->string('international_passport_number');
            $table->date('international_passport_expiry_date');
            $table->string('international_passport_image');
            $table->string('international_passport_image_url');
            $table->string('international_passport_image_driver');
            $table->json('guarantor');
            $table->json('travel_sponsor')->nullable();
            $table->json('details');
            $table->json('active_installment')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
