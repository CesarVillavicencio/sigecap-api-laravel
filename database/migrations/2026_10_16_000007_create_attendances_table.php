<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->unique()->constrained('registrations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('registered_by');
            $table->unsignedBigInteger('registered_by_responsibility_id');
            $table->timestamp('check_in_at')->useCurrent();
            $table->string('registration_method', 50)->default('qr');
            $table->string('device', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('registered_by', 'idx_attendances_registered_by');
            $table->index('check_in_at', 'idx_attendances_check_in_at');
        });

        DB::statement("ALTER TABLE attendances ADD CONSTRAINT chk_attendances_method CHECK (registration_method IN ('qr','folio','manual'))");

    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
