<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // account owner
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->enum('role', ['agent', 'supervisor', 'admin'])->default('agent');
            $table->enum('status', ['online', 'offline', 'busy'])->default('offline');
            $table->unsignedSmallInteger('max_concurrent_chats')->default(10);
            $table->unsignedSmallInteger('active_chat_count')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
