<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_notes', function (Blueprint $table) {
            $table->string('title', 120)->nullable()->after('agent_name');
            $table->string('note_type', 20)->default('text')->after('title'); // text | voice | drawing
            $table->json('attachments')->nullable()->after('note');            // [{url, name, mime}]
        });
    }

    public function down(): void
    {
        Schema::table('chat_notes', function (Blueprint $table) {
            $table->dropColumn(['title', 'note_type', 'attachments']);
        });
    }
};
