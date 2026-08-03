<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('webhook_trigger_id')->nullable()->constrained()->nullOnDelete();
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'skipped', 'failed'])->default('received');
            $table->string('resolved_phone')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
