<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('last_executed_at');
        });
    }

    public function down()
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
