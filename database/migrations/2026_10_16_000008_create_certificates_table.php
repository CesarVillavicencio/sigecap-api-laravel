<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->unique()->constrained('registrations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('certificate_folio', 100)->unique();
            $table->string('verification_token', 100)->unique();
            $table->string('file_path', 500)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->index('issued_at', 'idx_certificates_issued_at');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
