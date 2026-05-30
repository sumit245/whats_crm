<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('category', ['MARKETING', 'UTILITY', 'AUTHENTICATION'])->nullable()->after('type');
            $table->foreignId('template_id')->nullable()->after('category')
                ->constrained('waba_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['category', 'template_id']);
        });
    }
};
