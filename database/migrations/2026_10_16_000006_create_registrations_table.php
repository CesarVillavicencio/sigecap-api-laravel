<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('course_edition_id')->constrained('course_editions')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('folio', 50)->unique();
            $table->string('qr_token', 100)->unique();
            $table->string('status', 50)->default('registered');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['participant_id', 'course_edition_id'], 'uq_registration_participant_edition');
            $table->index('status', 'idx_registrations_status');
            $table->index('registered_at', 'idx_registrations_registered_at');
        });

        DB::statement("ALTER TABLE registrations ADD CONSTRAINT chk_registrations_status CHECK (status IN ('registered','cancelled'))");

    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
