<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEdition;
use App\Models\Dependency;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class PublicRegistrationService
{
    public function register(CourseEdition $edition, array $data): Registration
    {
        return DB::transaction(function () use ($edition, $data) {
            $courseId = $edition->course_id;
            $edition = CourseEdition::lockForUpdate()->findOrFail($edition->id);
            if ((string) $edition->course_id !== (string) $courseId) {
                $this->conflict('INVALID_REFERENCE', 'La edición cambió de curso. Vuelve a abrir la ficha.');
            }
            $course = Course::lockForUpdate()->findOrFail($edition->course_id);
            if (! $course->active || ! Dependency::whereKey($data['dependency_id'])->where('active', true)->lockForUpdate()->first()) {
                $this->conflict('INACTIVE_RESOURCE', 'El curso o la dependencia no están activos.');
            }
            if ($edition->status !== 'registration_open'
                || ($edition->registration_start && now()->lt($edition->registration_start))
                || ($edition->registration_end && now()->gt($edition->registration_end))) {
                $this->conflict('REGISTRATION_CLOSED', 'Las inscripciones están cerradas.');
            }
            if ($edition->max_capacity !== null && $edition->registrations()->where('status', 'registered')->count() >= $edition->max_capacity) {
                $this->conflict('CAPACITY_EXCEEDED', 'Esta edición ya no tiene lugares disponibles.');
            }
            $participant = Participant::where('curp', $data['curp'])->lockForUpdate()->first();
            if ($participant) {
                if (! $participant->active || strcasecmp($participant->institutional_email, $data['institutional_email']) !== 0
                    || (string) $participant->dependency_id !== (string) $data['dependency_id']) {
                    $this->conflict('PARTICIPANT_MISMATCH', 'Los datos no coinciden con el participante registrado. Contacta al organizador.');
                }
            } else {
                $participant = Participant::create($data);
            }
            if (Registration::where('participant_id', $participant->id)->where('course_edition_id', $edition->id)->exists()) {
                $this->conflict('DUPLICATE_REGISTRATION', 'Ya existe una inscripción para este participante en la edición.');
            }

            return Registration::create([
                'participant_id' => $participant->id,
                'course_edition_id' => $edition->id,
                'status' => 'registered',
                'folio' => 'SIG-'.implode('-', str_split(strtoupper(bin2hex(random_bytes(6))), 4)),
                'qr_token' => bin2hex(random_bytes(32)),
                'registered_at' => now(),
            ]);
        }, 3);
    }

    private function conflict(string $code, string $message): never
    {
        throw new HttpResponseException(response()->json(['error' => compact('code', 'message')], 409));
    }
}
