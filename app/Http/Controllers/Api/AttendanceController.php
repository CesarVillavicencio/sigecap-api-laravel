<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AttendanceController extends CrudController
{
    protected string $model = Attendance::class;

    protected array $filters = ['registration_id', 'registered_by'];

    protected function rules(?Model $record): array
    {
        return [
            'registration_method' => 'required|in:qr,folio,manual',
            'registration_id' => 'sometimes|integer|min:1|exists:registrations,id',
            'qr_token' => ['sometimes', 'string', 'regex:/^[a-fA-F0-9]{64}$/'],
            'folio' => ['sometimes', 'string', 'regex:/^SIG-(?:[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}|[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12})$/i'],
            'course_edition_id' => 'sometimes|integer|min:1|exists:course_editions,id',
            'device' => 'sometimes|nullable|string|max:150',
            'notes' => 'sometimes|nullable|string|max:20000',
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $record): array
    {
        // Serializar tambien contra la emision de constancias de la inscripcion original.
        $originalRegistration = $record
            ? Registration::whereKey($record->registration_id)->lockForUpdate()->first()
            : null;
        $method = $data['registration_method'] ?? $record?->registration_method;
        $key = ['qr' => 'qr_token', 'folio' => 'folio', 'manual' => 'registration_id'][$method];
        $identifiers = array_intersect_key($data, array_flip(['qr_token', 'folio', 'registration_id']));
        $resolving = ! $record || $identifiers || (isset($data['registration_method']) && $method !== $record->registration_method);
        if ($resolving) {
            if (! isset($data[$key]) || count($identifiers) !== 1) {
                $this->fail('VALIDATION_ERROR', 'Envia solo el identificador correspondiente al metodo de registro.', 422);
            }
            $value = $data[$key];
            if ($key === 'qr_token') {
                $value = strtolower($value);
            } elseif ($key === 'folio') {
                $value = strlen($value) === 40 ? 'SIG-'.strtolower(substr($value, 4)) : strtoupper($value);
            }
            $registration = Registration::where($key === 'registration_id' ? 'id' : $key, $value)->lockForUpdate()->first();
        } else {
            $registration = Registration::whereKey($record->registration_id)->lockForUpdate()->first();
        }
        if (! $registration) {
            $this->fail('NOT_FOUND', 'No se encontro la inscripcion.', 404);
        }
        if ($registration->status !== 'registered') {
            $this->fail('REGISTRATION_CANCELLED', 'La inscripcion esta cancelada.');
        }
        if (isset($data['course_edition_id']) && (string) $data['course_edition_id'] !== (string) $registration->course_edition_id) {
            $this->fail('ATTENDANCE_EDITION_MISMATCH', 'La inscripcion pertenece a otra edicion.');
        }
        if ($record && (string) $record->registration_id !== (string) $registration->id && $originalRegistration?->certificate()->exists()) {
            $this->fail('ATTENDANCE_HAS_CERTIFICATE', 'No se puede reasignar una asistencia que respalda una constancia.');
        }
        $duplicate = Attendance::where('registration_id', $registration->id);
        if ($record) {
            $duplicate->where('id', '!=', $record->id);
        }
        if ($duplicate->exists()) {
            $this->fail('ATTENDANCE_ALREADY_RECORDED', 'La inscripcion ya tiene asistencia.');
        }
        unset($data['qr_token'], $data['folio'], $data['course_edition_id']);
        $data['registration_id'] = $registration->id;
        if (! $record) {
            $data = array_merge($data, $this->identity($request, 'registered_by'));
            $data['check_in_at'] = now();
        }

        return $data;
    }

    protected function remove(Model $record): void
    {
        $registration = Registration::whereKey($record->registration_id)->lockForUpdate()->first();
        if ($registration?->certificate()->exists()) {
            $this->fail('ATTENDANCE_HAS_CERTIFICATE', 'Elimina la constancia antes de retirar su asistencia.');
        }
        $record->delete();
    }
}
