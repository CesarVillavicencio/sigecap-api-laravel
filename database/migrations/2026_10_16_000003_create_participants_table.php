<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependency_id')->nullable()->constrained('dependencies')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('dependency_name', 200)->nullable();
            $table->string('first_name', 100);
            $table->string('first_last_name', 100);
            $table->string('second_last_name', 100)->nullable();
            $table->string('curp', 18)->unique();
            $table->string('area', 150)->nullable();
            $table->string('position', 150)->nullable();
            $table->string('institutional_email', 180);
            $table->string('phone', 30)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('institutional_email', 'idx_participants_email');
            $table->index('first_last_name', 'idx_participants_last_name');
        });

        DB::statement("ALTER TABLE participants ADD CONSTRAINT chk_participants_curp_length CHECK (char_length(curp) = 18)");
        DB::statement("ALTER TABLE participants ADD CONSTRAINT chk_participants_dependency_present CHECK (dependency_id IS NOT NULL OR NULLIF(BTRIM(dependency_name), '') IS NOT NULL)");

    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
