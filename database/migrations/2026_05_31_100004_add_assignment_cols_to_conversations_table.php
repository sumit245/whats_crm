<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('assigned_agent_id')->nullable()->after('fallback_count')
                ->constrained('agents')->nullOnDelete();
            $table->enum('assignment_source', ['auto', 'manual'])->nullable()->after('assigned_agent_id');
            $table->timestamp('assigned_at')->nullable()->after('assignment_source');
            $table->timestamp('first_response_at')->nullable()->after('assigned_at');
            $table->timestamp('resolved_at')->nullable()->after('first_response_at');
            $table->boolean('sla_breached')->default(false)->after('resolved_at');
            $table->enum('conversation_status', ['open', 'pending', 'resolved'])->default('open')->after('sla_breached');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['assigned_agent_id']);
            $table->dropColumn(['assigned_agent_id', 'assignment_source', 'assigned_at',
                'first_response_at', 'resolved_at', 'sla_breached', 'conversation_status']);
        });
    }
};
