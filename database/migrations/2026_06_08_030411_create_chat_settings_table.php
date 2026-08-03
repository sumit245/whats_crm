<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('timezone', 64)->default('UTC');
            $table->json('working_hours')->nullable();
            $table->boolean('off_hours_enabled')->default(false);
            $table->text('off_hours_message')->nullable();
            $table->boolean('auto_resolve_enabled')->default(false);
            $table->unsignedSmallInteger('auto_resolve_hours')->default(24);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_settings');
    }
};
