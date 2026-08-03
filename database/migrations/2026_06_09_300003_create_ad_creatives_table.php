<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_creatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('format', ['text', 'image', 'video', 'carousel', 'story', 'reel'])->default('text');
            $table->string('headline', 255)->nullable();
            $table->text('body')->nullable();
            $table->string('cta_text', 50)->nullable();
            $table->string('cta_url')->nullable();
            $table->json('media_paths')->nullable();      // ["storage/ads/img.jpg", ...]
            $table->json('carousel_cards')->nullable();   // [{image, headline, description, cta_url}, ...]
            $table->enum('status', ['draft', 'ready', 'rejected'])->default('draft');
            $table->timestamps();

            $table->index(['user_id', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_creatives');
    }
};
