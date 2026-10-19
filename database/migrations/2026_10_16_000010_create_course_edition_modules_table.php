<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_edition_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_edition_id')->constrained('course_editions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->integer('display_order')->default(1);
            $table->timestamps();
            $table->index(['course_edition_id', 'display_order'], 'idx_edition_modules_order');
        });

        DB::statement('ALTER TABLE course_edition_modules ADD CONSTRAINT chk_edition_modules_order CHECK (display_order > 0)');

    }

    public function down(): void
    {
        Schema::dropIfExists('course_edition_modules');
    }
};
