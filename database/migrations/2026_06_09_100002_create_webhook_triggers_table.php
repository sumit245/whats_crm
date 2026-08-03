<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_source_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            // Event matching — leave both null to match every inbound webhook
            $table->string('match_field')->nullable();  // dot-notation path to check in payload
            $table->string('match_value')->nullable();  // expected value (exact string match)

            // Action
            $table->enum('action_type', ['send_template', 'start_flow'])->default('send_template');

            // For send_template
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_name')->nullable();
            $table->string('template_language')->default('en');
            // JSON: {"1": "order.name", "2": "order.total"} — maps template vars to payload paths
            $table->json('variable_map')->nullable();

            // For start_flow
            $table->foreignId('flow_id')->nullable()->constrained('chatbot_flows')->nullOnDelete();

            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_triggers');
    }
};
