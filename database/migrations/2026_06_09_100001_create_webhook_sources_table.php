<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('platform')->default('generic'); // shopify, woocommerce, razorpay, generic
            $table->string('token', 64)->unique();          // used in public URL: /wh/{token}
            $table->string('secret')->nullable();           // HMAC signature secret (optional)
            $table->string('phone_field')->default('phone'); // dot-notation path to phone in payload
            $table->string('name_field')->nullable();        // dot-notation path to contact name
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_sources');
    }
};
