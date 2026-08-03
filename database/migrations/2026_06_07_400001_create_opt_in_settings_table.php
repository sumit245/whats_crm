<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opt_in_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('opt_out_keywords')->nullable();
            $table->string('opt_in_keyword', 64)->default('JOIN');
            $table->foreignId('opt_in_phonebook_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->text('opt_in_reply')->nullable();
            $table->text('opt_out_reply')->nullable();
            $table->boolean('opt_in_enabled')->default(true);
            $table->boolean('opt_out_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opt_in_settings');
    }
};
