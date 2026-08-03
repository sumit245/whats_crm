<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_histories', function (Blueprint $table) {
            $table->string('meta_message_id')->nullable()->after('status')->index();
            $table->enum('delivery_status', ['sent', 'delivered', 'read', 'failed'])->nullable()->after('meta_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('message_histories', function (Blueprint $table) {
            $table->dropColumn(['meta_message_id', 'delivery_status']);
        });
    }
};
