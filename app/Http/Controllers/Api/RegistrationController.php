<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use App\Models\CourseEdition;
use App\Models\Participant;
use App\Models\Registration;
use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationController extends CrudController
{
    protected string $model = Registration::class;

    protected array $filters = ['participant_id', 'course_edition_id', 'status'];

    public function byCourse(string $courseId): StreamedResponse
    {
        $course = Course::find($courseId);
        if (! $course) {
            $this->fail('NOT_FOUND', 'El curso no existe.', 404);
        }

        return response()->streamDownload(function () use ($courseId, $course) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Registros');
            $sheet->mergeCells('A1:D1');
            $sheet->setCellValueExplicit('A1', $course->name, DataType::TYPE_STRING);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->fromArray([
                'Folio', 'Estado', 'Nombre completo', 'Dependencia',
            ], null, 'A3');

            try {
                $registrations = Registration::with('participant.dependency')
                    ->whereHas('courseEdition', fn ($query) => $query->where('course_id', $courseId))
                    ->lazyById(500);
                $row = 4;
                foreach ($registrations as $registration) {
                    $participant = $registration->participant;
                    $values = [
                        $registration->folio,
                        $registration->status,
                        collect([
                            $participant?->first_name,
                            $participant?->first_last_name,
                            $participant?->second_last_name,
                        ])->filter()->implode(' '),
                        $participant?->dependency?->name ?? $participant?->dependency_name ?? '',
                    ];

                    foreach ($values as $column => $value) {
                        // Los datos capturados se escriben como texto, nunca como fórmulas.
                        $sheet->setCellValueExplicit([$column + 1, $row], (string) $value, DataType::TYPE_STRING);
                    }
                    $row++;
                }

                $sheet->getStyle('A3:D3')->getFont()->setBold(true);
                $sheet->freezePane('A4');
                $sheet->setAutoFilter('A3:D'.($row - 1));
                foreach (range('A', 'D') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, 'registros-curso-'.$courseId.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    protected function rules(?Model $record): array
    {
        return [
            'participant_id' => 'required|integer|min:1|exists:participants,id',
            'course_edition_id' => 'required|integer|min:1|exists:course_editions,id',
            'status' => 'sometimes|in:registered,cancelled',
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $record): array
    {
        $values = array_replace($record?->getAttributes() ?? ['status' => 'registered'], $data);
        $edition = CourseEdition::lockForUpdate()->find($values['course_edition_id']);
        if (! $edition) {
            $this->fail('INVALID_REFERENCE', 'La edicion ya no existe.');
        }
        $moved = $record && ((string) $values['participant_id'] !== (string) $record->participant_id
            || (string) $values['course_edition_id'] !== (string) $record->course_edition_id);
        if ($moved && ($record->attendance()->exists() || $record->certificate()->exists())) {
            $this->fail('REGISTRATION_HAS_HISTORY', 'No se puede reasignar una inscripcion con asistencia o constancia.');
        }
        $duplicate = Registration::where('participant_id', $values['participant_id'])
            ->where('course_edition_id', $values['course_edition_id']);
        if ($record) {
            $duplicate->where('id', '!=', $record->getKey());
        }
        if ($duplicate->exists()) {
            $this->fail('DUPLICATE_REGISTRATION', 'El participante ya tiene una inscripcion en esta edicion.');
        }

        $activating = $values['status'] === 'registered' && (! $record || $record->status !== 'registered' || $moved);
        if ($activating) {
            $participant = Participant::lockForUpdate()->find($values['participant_id']);
            $course = Course::lockForUpdate()->find($edition->course_id);
            if (! $participant?->active || ! $course?->active) {
                $this->fail('INACTIVE_RESOURCE', 'El participante y el curso deben estar activos.');
            }
            $now = now();
            if ($edition->status !== 'registration_open'
                || ($edition->registration_start && $now->lt($edition->registration_start))
                || ($edition->registration_end && $now->gt($edition->registration_end))) {
                $this->fail('REGISTRATION_CLOSED', 'La edicion no tiene inscripciones abiertas en este momento.');
            }
            $occupied = $edition->registrations()->where('status', 'registered');
            if ($record) {
                $occupied->where('id', '!=', $record->getKey());
            }
            if ($edition->max_capacity !== null && $occupied->count() >= $edition->max_capacity) {
                $this->fail('CAPACITY_EXCEEDED', 'La edicion ya no tiene cupo disponible.');
            }
        }
        if (! $record) {
            $data['folio'] = 'SIG-'.implode('-', str_split(strtoupper(bin2hex(random_bytes(6))), 4));
            $data['qr_token'] = bin2hex(random_bytes(32));
            $data['registered_at'] = now();
        }

        return $data;
    }

    protected function remove(Model $record): void
    {
        CourseEdition::whereKey($record->course_edition_id)->lockForUpdate()->first();
        $record->update(['status' => 'cancelled']);
    }

    public function qr(string $id, QrCodeService $qr): Response
    {
        $registration = $this->find($id);

        return $qr->download($registration->qr_token, 'inscripcion-'.$registration->id.'.svg');
    }
}
