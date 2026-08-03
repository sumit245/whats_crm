<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drip_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft'); // draft, active, paused
            // trigger_type: manual | opt_in | phonebook_add
            $table->string('trigger_type', 30)->default('manual');
            // phonebook to auto-enroll from (for phonebook_add trigger)
            $table->foreignId('trigger_phonebook_id')->nullable()->constrained('tags')->nullOnDelete();
            // device to use for sending when enrolled via trigger (optional — enrollment can override)
            $table->foreignId('default_device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('drip_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('drip_sequences')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');  // 1-based ordering
            // Delay before this step executes (after previous step or enrollment)
            $table->unsignedSmallInteger('delay_value')->default(0);
            $table->string('delay_unit', 10)->default('hours'); // minutes, hours, days
            // Message
            $table->string('message_type', 20)->default('text'); // text | template
            $table->text('message_body')->nullable();             // for text type
            $table->foreignId('template_id')->nullable()->constrained('waba_templates')->nullOnDelete();
            // Behaviour
            $table->boolean('skip_if_replied')->default(false);
            $table->timestamps();

            $table->unique(['sequence_id', 'step_order']);
        });

        Schema::create('drip_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('drip_sequences')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('contact_number', 30)->index();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            // current_step = step_order of the NEXT step to execute (0 = not started yet)
            $table->unsignedSmallInteger('current_step')->default(1);
            $table->string('status', 20)->default('active'); // active, completed, cancelled, failed, paused
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            // Prevent duplicate active enrollment per contact+sequence
            $table->unique(['sequence_id', 'contact_number', 'status'], 'drip_active_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_enrollments');
        Schema::dropIfExists('drip_steps');
        Schema::dropIfExists('drip_sequences');
    }
};
