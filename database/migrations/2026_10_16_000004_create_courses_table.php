<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('created_by_responsibility_id')->nullable();
            $table->string('name', 250);
            $table->string('activity', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('objective')->nullable();
            $table->text('target_audience')->nullable();
            $table->decimal('duration_hours', 6, 2)->nullable();
            $table->string('modality', 50)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('created_by', 'idx_courses_created_by');
        });

        DB::statement('ALTER TABLE courses ADD CONSTRAINT chk_courses_duration CHECK (duration_hours IS NULL OR duration_hours > 0)');

    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
