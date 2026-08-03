<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('language', 10)->index();
            $table->string('category')->index();
            $table->string('topic')->nullable();
            $table->string('usecase')->nullable();
            $table->string('industry')->nullable();
            $table->text('header')->nullable();
            $table->text('body')->nullable();
            $table->string('footer')->nullable();
            $table->json('buttons')->nullable();
            $table->string('meta_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['name', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_templates');
    }
};
