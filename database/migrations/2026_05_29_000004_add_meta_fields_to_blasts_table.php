<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blasts', function (Blueprint $table) {
            $table->string('meta_message_id')->nullable()->after('status')->index();
            $table->json('template_variables')->nullable()->after('meta_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('blasts', function (Blueprint $table) {
            $table->dropIndex(['meta_message_id']);
            $table->dropColumn(['meta_message_id', 'template_variables']);
        });
    }
};
