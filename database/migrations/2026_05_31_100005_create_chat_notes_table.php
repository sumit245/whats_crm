<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('agent_name')->nullable(); // denormalized for display after agent deletion
            $table->text('note');
            $table->boolean('is_internal')->default(true); // true = whisper (not sent to WA), false = visible
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_notes');
    }
};
