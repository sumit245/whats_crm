<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waba_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->string('name');
            $table->string('meta_template_id')->nullable();
            $table->enum('category', ['MARKETING', 'UTILITY', 'AUTHENTICATION']);
            $table->string('language', 10)->default('en');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'PAUSED', 'DISABLED'])->default('PENDING');
            $table->json('components');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('meta_synced_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waba_templates');
    }
};
