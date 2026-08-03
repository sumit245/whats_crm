<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('objective', ['awareness', 'traffic', 'engagement', 'leads', 'ctwa', 'sales'])->default('traffic');
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'failed'])->default('draft');
            $table->decimal('budget_total', 12, 2)->nullable();
            $table->decimal('budget_daily', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('bid_strategy', ['lowest_cost', 'target_cost', 'bid_cap'])->default('lowest_cost');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->foreignId('target_segment_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->json('audience_settings')->nullable();  // age_min, age_max, genders, locations, interests
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
