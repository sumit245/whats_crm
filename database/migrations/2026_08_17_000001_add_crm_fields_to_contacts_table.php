<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Safety net before the dedupe/tag_id drop in the next migration —
        // drop this table once the CRM rollout is verified in production.
        DB::statement('CREATE TABLE IF NOT EXISTS contacts_backup_pre_crm AS SELECT * FROM contacts');

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('company')->nullable()->after('name');
            $table->string('email')->nullable()->after('company');
            $table->text('address')->nullable()->after('email');
            $table->string('linkedin_url')->nullable()->after('address');
            $table->string('facebook_url')->nullable()->after('linkedin_url');
            $table->string('website')->nullable()->after('facebook_url');
            $table->string('source')->nullable()->after('website');
            $table->string('status')->nullable()->after('source');
            $table->text('remarks')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'company', 'email', 'address', 'linkedin_url',
                'facebook_url', 'website', 'source', 'status', 'remarks',
            ]);
        });

        Schema::dropIfExists('contacts_backup_pre_crm');
    }
};
