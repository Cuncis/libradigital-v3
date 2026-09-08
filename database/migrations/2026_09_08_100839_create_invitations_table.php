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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('invitations')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();

            // Layup Page-compatible columns (Invitation subclasses Crumbls\Layup\Models\Page).
            $table->string('title');
            $table->string('slug')->index();
            $table->string('path')->unique();
            $table->json('content')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('author')->nullable();

            // Invitation-specific columns.
            $table->string('host_name')->nullable();
            $table->string('venue')->nullable();
            $table->dateTime('event_date')->nullable();
            $table->boolean('is_custom_build')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
