<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogue_id')->constrained()->onDelete('cascade');
            $table->string('retailer_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->default(0); // in minor units (cents/paise)
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('image_url')->nullable();
            $table->string('product_url')->nullable();
            $table->string('availability')->default('in stock'); // in stock, out of stock, preorder
            $table->string('condition')->default('new');         // new, refurbished, used
            $table->string('brand')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();

            $table->unique(['catalogue_id', 'retailer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_products');
    }
};
