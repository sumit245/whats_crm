<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_delivery_events', function (Blueprint $table) {
            $table->id();
            $table->string('meta_message_id')->index();
            $table->foreignId('blast_id')->nullable()->constrained('blasts')->nullOnDelete();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->enum('status', ['sent', 'delivered', 'read', 'failed']);
            $table->string('error_code')->nullable();
            $table->string('error_title')->nullable();
            $table->timestamp('event_timestamp');
            $table->timestamps();
            $table->index(['blast_id', 'status']);
            $table->unique(['meta_message_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_delivery_events');
    }
};
