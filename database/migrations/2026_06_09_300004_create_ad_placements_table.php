<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_creative_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('placement_type', ['feed', 'stories', 'reels', 'sponsored', 'ctwa', 'direct_message'])->default('feed');
            $table->enum('status', ['pending', 'active', 'paused', 'completed', 'failed', 'in_review'])->default('pending');
            $table->string('external_ad_id')->nullable();         // platform's ad ID
            $table->string('platform_campaign_id')->nullable();   // platform's campaign ID
            $table->string('platform_adset_id')->nullable();      // platform's ad set ID
            $table->decimal('budget_override', 12, 2)->nullable();
            $table->json('metrics_cache')->nullable();            // last known totals snapshot
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['ad_campaign_id', 'status']);
            $table->index('ad_channel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_placements');
    }
};
