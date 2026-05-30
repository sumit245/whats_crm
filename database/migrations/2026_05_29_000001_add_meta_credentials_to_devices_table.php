<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('phone_number_id')->nullable()->after('body');
            $table->string('waba_id')->nullable()->after('phone_number_id');
            $table->text('access_token')->nullable()->after('waba_id');
            $table->string('quality_rating')->nullable()->after('status'); // GREEN | YELLOW | RED
            $table->string('messaging_tier')->nullable()->after('quality_rating'); // TIER_1K | TIER_10K | TIER_100K
            $table->json('meta_profile')->nullable()->after('messaging_tier'); // display_phone_number, verified_name
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['phone_number_id', 'waba_id', 'access_token', 'quality_rating', 'messaging_tier', 'meta_profile']);
        });
    }
};
