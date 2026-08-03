<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('meta_catalog_id')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('vertical')->default('COMMERCE');
            $table->unsignedInteger('product_count')->default(0);
            $table->string('business_id')->nullable();
            $table->boolean('is_linked')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogues');
    }
};
