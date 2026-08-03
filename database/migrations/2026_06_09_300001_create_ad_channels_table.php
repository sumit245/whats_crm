<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['meta', 'facebook', 'instagram', 'telegram', 'email', 'linkedin']);
            $table->string('name');
            $table->enum('status', ['active', 'inactive', 'error'])->default('inactive');
            $table->text('credentials')->nullable();   // Crypt::encryptString(json)
            $table->json('metadata')->nullable();      // display name, avatar, followers, etc.
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_channels');
    }
};
