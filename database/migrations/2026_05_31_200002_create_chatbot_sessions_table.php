<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flow_id')->constrained('chatbot_flows')->cascadeOnDelete();
            // Drawflow node ID (string key in the flow_json data object)
            $table->string('current_node_id')->nullable();
            $table->enum('state', [
                'bot_active',
                'awaiting_input',
                'human_assigned',
                'completed',
            ])->default('bot_active');
            // Variable store — values collected via ask_input nodes
            $table->json('variables')->nullable();
            // The variable name we're waiting for (when state = awaiting_input)
            $table->string('awaiting_variable')->nullable();
            $table->unsignedTinyInteger('fallback_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'state']);
            $table->index('flow_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_sessions');
    }
};
