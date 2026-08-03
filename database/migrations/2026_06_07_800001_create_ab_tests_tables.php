<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('phonebook_id')->constrained('tags')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->unsignedTinyInteger('holdout_percent')->default(20);
            $table->string('winner_metric', 20)->default('read_rate'); // read_rate|delivery_rate|click_rate
            $table->unsignedSmallInteger('decide_after_hours')->default(24);
            $table->timestamp('decide_at')->nullable();
            $table->unsignedBigInteger('winner_variant_id')->nullable();
            $table->foreignId('winner_campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft|running|completed|cancelled
            $table->timestamps();
        });

        Schema::create('ab_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->cascadeOnDelete();
            $table->string('label', 4);           // A, B
            $table->string('message_type', 20)->default('text'); // text|template
            $table->text('message_body')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('waba_templates')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ab_holdout_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->cascadeOnDelete();
            $table->string('contact_number', 30);
            $table->index(['ab_test_id', 'contact_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_holdout_contacts');
        Schema::dropIfExists('ab_variants');
        Schema::dropIfExists('ab_tests');
    }
};
