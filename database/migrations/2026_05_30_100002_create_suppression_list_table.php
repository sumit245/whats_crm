<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppression_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('number', 20);
            $table->enum('reason', ['user_optout', 'meta_block', 'manual'])->default('manual');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'number']);
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppression_list');
    }
};
