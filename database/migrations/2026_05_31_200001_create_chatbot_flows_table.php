<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'draft'])->default('draft');
            // Trigger configuration
            $table->enum('trigger_type', ['keyword', 'all', 'referral', 'api'])->default('keyword');
            $table->string('trigger_value')->nullable();          // keyword string or referral id
            $table->enum('trigger_match', ['exact', 'contains', 'starts_with'])->default('contains');
            // The Drawflow canvas JSON (can be large)
            $table->longText('flow_json')->nullable();
            // Bot fallback: sent when bot can't understand after 2 tries
            $table->text('fallback_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'device_id', 'status']);
            $table->index('trigger_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_flows');
    }
};
