<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_node_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('chatbot_flows')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('chatbot_sessions')->cascadeOnDelete();
            $table->string('node_id', 64);
            $table->string('node_type', 64)->default('unknown');
            // 'entered' when node starts executing, 'completed' when session finishes
            $table->string('event_type', 20)->default('entered');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['flow_id', 'node_id']);
            $table->index(['flow_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_node_events');
    }
};
