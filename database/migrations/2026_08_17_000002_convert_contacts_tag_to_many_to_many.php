<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_tag', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['contact_id', 'tag_id']);
            $table->timestamp('created_at')->nullable();
        });

        // Determine the surviving contact_id per (user_id, number): the
        // most-recently-updated row (ties broken by highest id) — same rule
        // used by the dedupe delete below, so the two stay consistent.
        DB::statement(
            "CREATE TEMPORARY TABLE contact_keep AS
             SELECT c1.user_id, c1.number, c1.id AS keep_id
             FROM contacts c1
             WHERE c1.id = (
                 SELECT c2.id FROM contacts c2
                 WHERE c2.user_id = c1.user_id AND c2.number = c1.number
                 ORDER BY c2.updated_at DESC, c2.id DESC
                 LIMIT 1
             )"
        );

        // Backfill the pivot against the SURVIVING contact_id, from every
        // pre-dedupe row's tag_id — not just the keeper's own — so a tag
        // membership carried only by a row that's about to be deleted isn't
        // lost. INSERT IGNORE handles two duplicate rows sharing a tag_id.
        DB::statement(
            "INSERT IGNORE INTO contact_tag (contact_id, tag_id, created_at)
             SELECT k.keep_id, c.tag_id, NOW()
             FROM contacts c
             JOIN contact_keep k ON k.user_id = c.user_id AND k.number = c.number
             WHERE c.tag_id IS NOT NULL"
        );

        // Now dedupe contacts on (user_id, number): delete every row that
        // isn't the keeper — its tag membership is already preserved above.
        DB::statement(
            "DELETE c FROM contacts c
             JOIN contact_keep k ON k.user_id = c.user_id AND k.number = c.number
             WHERE c.id != k.keep_id"
        );

        DB::statement('DROP TEMPORARY TABLE contact_keep');

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign('contacts_tag_id_foreign');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('tag_id');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->unique(['user_id', 'number']);
        });
    }

    public function down(): void
    {
        // Best-effort reverse — original per-tag row duplication and exact
        // tag_id values are not reconstructable; this restores structure only.
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'number']);
            $table->foreignId('tag_id')->nullable()->after('user_id')
                ->constrained('tags')->nullOnDelete();
        });

        Schema::dropIfExists('contact_tag');
    }
};
