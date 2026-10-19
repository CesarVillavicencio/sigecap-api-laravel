<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('position', 200)->nullable();
            $table->string('institution', 200)->nullable();
            $table->string('email', 180)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('name', 'idx_instructors_name');
            $table->index('institution', 'idx_instructors_institution');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
