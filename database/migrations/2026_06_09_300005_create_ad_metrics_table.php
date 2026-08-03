<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_placement_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('spend', 12, 4)->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('cpm', 8, 4)->default(0);
            $table->decimal('cpc', 8, 4)->default(0);
            $table->json('channel_raw')->nullable();  // raw API response for auditing
            $table->timestamps();

            $table->unique(['ad_placement_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_metrics');
    }
};
