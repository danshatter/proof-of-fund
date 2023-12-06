<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\{User, Role};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Role::class)->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('business_name')->nullable()->unique();
            $table->string('business_website')->nullable();
            $table->string('business_state')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->longText('request_message')->nullable();
            $table->string('email_verification')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->longText('verification')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->integer('failed_verification_attempts')->nullable();
            $table->timestamp('locked_due_to_failed_verification_at')->nullable();
            $table->integer('failed_login_attempts')->nullable();
            $table->timestamp('locked_due_to_failed_login_attempts_at')->nullable();
            $table->longText('image')->nullable();
            $table->foreignIdFor(User::class, 'referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('referral_code')->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
