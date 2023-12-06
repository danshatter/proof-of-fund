<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Application;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Application::class)->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('amount')->nullable();
            $table->string('message')->nullable();
            $table->string('reference')->nullable()->index();
            $table->string('transfer_code')->nullable();
            $table->string('recipient_code')->nullable();
            $table->string('customer_code')->nullable();
            $table->string('type')->nullable()->index();
            $table->string('channel')->index()->nullable();
            $table->string('currency')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
