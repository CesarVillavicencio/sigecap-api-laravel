<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_edition_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_edition_id')->constrained('course_editions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('instructors')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('course_edition_modules')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('topic', 250)->nullable();
            $table->integer('display_order')->default(1);
            $table->timestamps();
            $table->unique(['course_edition_id', 'instructor_id', 'module_id'], 'uq_edition_instructor_module');
        });

        DB::statement('ALTER TABLE course_edition_instructors ADD CONSTRAINT chk_edition_instructors_order CHECK (display_order > 0)');
        DB::statement('CREATE UNIQUE INDEX uq_edition_instructor_without_module ON course_edition_instructors (course_edition_id, instructor_id) WHERE module_id IS NULL');

    }

    public function down(): void
    {
        Schema::dropIfExists('course_edition_instructors');
    }
};
