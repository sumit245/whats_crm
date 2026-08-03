<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->json('definition');                         // targeting params
            $table->string('external_audience_id')->nullable(); // platform's Custom Audience ID
            $table->unsignedBigInteger('estimated_size')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_audiences');
    }
};
