<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('winner_metric', ['ctr', 'conversions', 'clicks'])->default('ctr');
            $table->unsignedSmallInteger('decide_after_hours')->default(24);
            $table->timestamp('launched_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('winner_creative_id')->nullable()->constrained('ad_creatives')->nullOnDelete();
            $table->enum('status', ['draft', 'running', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();
        });

        Schema::create('ad_ab_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_ab_test_id')->constrained()->cascadeOnDelete();
            $table->string('label', 4);     // A, B, C
            $table->foreignId('ad_creative_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('budget_split')->default(50); // % of budget
            $table->timestamps();
        });

        // Track which variant each placement belongs to
        Schema::table('ad_placements', function (Blueprint $table) {
            $table->foreignId('ad_ab_variant_id')->nullable()->after('ad_creative_id')
                ->constrained('ad_ab_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ad_placements', function (Blueprint $table) {
            $table->dropForeign(['ad_ab_variant_id']);
            $table->dropColumn('ad_ab_variant_id');
        });
        Schema::dropIfExists('ad_ab_variants');
        Schema::dropIfExists('ad_ab_tests');
    }
};
