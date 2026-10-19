<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseEdition;
use App\Services\QrCodeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CourseEditionController extends CrudController
{
    protected string $model = CourseEdition::class;

    protected array $filters = ['course_id', 'status'];

    protected function rules(?Model $record): array
    {
        return [
            'course_id' => 'required|integer|min:1|exists:courses,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'sometimes|nullable|date_format:Y-m-d|after_or_equal:start_date',
            'start_time' => ['sometimes', 'nullable', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/'],
            'end_time' => ['sometimes', 'nullable', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/'],
            'location' => 'sometimes|nullable|string|max:250',
            'max_capacity' => 'sometimes|nullable|integer|min:1|max:2147483647',
            'participant_target' => 'sometimes|nullable|integer|min:0|max:2147483647',
            'registration_start' => 'sometimes|nullable|date',
            'registration_end' => 'sometimes|nullable|date',
            'status' => 'sometimes|in:draft,published,registration_open,registration_closed,in_progress,finished,cancelled',
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $record): array
    {
        $start = array_key_exists('registration_start', $data) ? $data['registration_start'] : $record?->registration_start;
        $end = array_key_exists('registration_end', $data) ? $data['registration_end'] : $record?->registration_end;
        if ($start && $end && Carbon::parse($end)->lt(Carbon::parse($start))) {
            $this->fail('VALIDATION_ERROR', 'El cierre de inscripcion debe ser posterior o igual a la apertura.', 422);
        }
        if ($record && isset($data['max_capacity']) && $data['max_capacity'] < $record->registrations()->where('status', 'registered')->count()) {
            $this->fail('CAPACITY_EXCEEDED', 'El cupo no puede ser menor a las inscripciones vigentes.');
        }
        if (! $record) {
            $data = array_merge($data, $this->identity($request, 'created_by'));
            $data['public_token'] = bin2hex(random_bytes(32));
        }

        return $data;
    }

    protected function remove(Model $record): void
    {
        if ($record->registrations()->exists()) {
            $this->fail('EDITION_HAS_REGISTRATIONS', 'La edicion tiene inscripciones; cambia su estado a cancelled.');
        }
        $record->delete();
    }

    public function qr(string $id, QrCodeService $qr): Response
    {
        $edition = $this->find($id);

        return $qr->download($qr->publicUrl('c/'.$edition->public_token), 'edicion-'.$edition->id.'.svg');
    }
}
