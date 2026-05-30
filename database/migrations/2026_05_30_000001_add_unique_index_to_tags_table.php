<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate tags keeping the lowest id per (user_id, name) pair
        DB::statement("
            DELETE t1 FROM tags t1
            INNER JOIN tags t2
            WHERE t1.id > t2.id
              AND t1.user_id = t2.user_id
              AND t1.name = t2.name
        ");

        Schema::table('tags', function (Blueprint $table) {
            $table->unique(['user_id', 'name'], 'tags_user_id_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique('tags_user_id_name_unique');
        });
    }
};
