<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_editions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('created_by_responsibility_id');
            $table->string('public_token', 100)->unique();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location', 250)->nullable();
            $table->integer('max_capacity')->nullable();
            $table->integer('participant_target')->nullable();
            $table->timestamp('registration_start')->nullable();
            $table->timestamp('registration_end')->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamps();
            $table->index('created_by', 'idx_course_editions_created_by');
            $table->index('status', 'idx_course_editions_status');
            $table->index('start_date', 'idx_course_editions_start_date');
        });

        DB::statement('ALTER TABLE course_editions ADD CONSTRAINT chk_course_editions_dates CHECK (end_date IS NULL OR end_date >= start_date)');
        DB::statement('ALTER TABLE course_editions ADD CONSTRAINT chk_course_editions_registration_dates CHECK (registration_start IS NULL OR registration_end IS NULL OR registration_end >= registration_start)');
        DB::statement('ALTER TABLE course_editions ADD CONSTRAINT chk_course_editions_capacity CHECK (max_capacity IS NULL OR max_capacity > 0)');
        DB::statement('ALTER TABLE course_editions ADD CONSTRAINT chk_course_editions_target CHECK (participant_target IS NULL OR participant_target >= 0)');
        DB::statement("ALTER TABLE course_editions ADD CONSTRAINT chk_course_editions_status CHECK (status IN ('draft','published','registration_open','registration_closed','in_progress','finished','cancelled'))");

    }

    public function down(): void
    {
        Schema::dropIfExists('course_editions');
    }
};
