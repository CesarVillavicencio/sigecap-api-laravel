<?php

namespace App\Http\Controllers\Api;

use App\Models\Certificate;
use App\Models\Registration;
use App\Services\CertificatePdfService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CertificateController extends CrudController
{
    protected string $model = Certificate::class;

    protected array $filters = ['registration_id'];

    public function pdf(string $id, CertificatePdfService $pdf): Response
    {
        $certificate = $this->find($id);
        $registration = $certificate->registration;
        if ($registration->status !== 'registered') {
            $this->fail('REGISTRATION_CANCELLED', 'La inscripcion esta cancelada.');
        }
        if (! $registration->attendance()->exists()) {
            $this->fail('ATTENDANCE_REQUIRED', 'La inscripcion debe tener asistencia.');
        }
        $participant = $registration->participant;
        $name = implode(' ', array_filter([
            $participant->first_name, $participant->first_last_name, $participant->second_last_name,
        ], fn ($value) => $value !== null && $value !== ''));

        return response($pdf->generate($name), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reconocimiento-'.$certificate->id.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    protected function rules(?Model $record): array
    {
        return [
            'registration_id' => 'required|integer|min:1|exists:registrations,id',
            'file_path' => 'sometimes|nullable|string|max:500',
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $record): array
    {
        $registration = Registration::whereKey($data['registration_id'] ?? $record?->registration_id)->lockForUpdate()->first();
        if (! $registration) {
            $this->fail('INVALID_REFERENCE', 'La inscripcion ya no existe.');
        }
        if ($registration->status !== 'registered') {
            $this->fail('REGISTRATION_CANCELLED', 'La inscripcion esta cancelada.');
        }
        if (! $registration->attendance()->exists()) {
            $this->fail('ATTENDANCE_REQUIRED', 'La inscripcion debe tener asistencia.');
        }
        $duplicate = Certificate::where('registration_id', $registration->id);
        if ($record) {
            $duplicate->where('id', '!=', $record->id);
        }
        if ($duplicate->exists()) {
            $this->fail('CERTIFICATE_ALREADY_ISSUED', 'La inscripcion ya tiene una constancia.');
        }
        if (! $record) {
            $data['certificate_folio'] = 'SIGC-'.strtoupper(bin2hex(random_bytes(8)));
            $data['verification_token'] = bin2hex(random_bytes(32));
            $data['issued_at'] = now();
        } elseif (isset($data['registration_id']) && (string) $data['registration_id'] !== (string) $record->registration_id) {
            // Invalidar enlaces anteriores cuando cambia la inscripcion de la constancia.
            $data['verification_token'] = bin2hex(random_bytes(32));
        }

        return $data;
    }
}
