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
        Schema::create('camouflages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('confidential');
            $table->string('confidential_hash')->index();
            $table->string('nationality')->nullable();
            $table->string('verification')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->integer('failed_verification_attempts')->nullable();
            $table->timestamp('locked_due_to_failed_verification_at')->nullable();
            $table->longText('image')->nullable();
            $table->timestamp('image_verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camouflages');
    }
};
