<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('contact_number');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'contact_number', 'key'], 'contact_attr_unique');
            $table->index(['user_id', 'contact_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_attributes');
    }
};
