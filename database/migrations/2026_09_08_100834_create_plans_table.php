<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tier');
            $table->string('billing_interval');
            $table->unsignedBigInteger('price');
            $table->string('currency')->default('IDR');
            $table->unsignedInteger('invitation_limit')->nullable();
            $table->json('features')->nullable();
            $table->string('mayar_tier_id')->nullable();
            $table->timestamps();

            $table->unique(['tier', 'billing_interval']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
