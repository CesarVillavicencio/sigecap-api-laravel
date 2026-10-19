<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEdition;
use App\Models\Dependency;
use App\Services\PublicRegistrationService;
use App\Services\QrCodeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Mail\RegistrationConfirmationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

abstract class PublicCoursePageController extends Controller
{
    protected function course(string $id): Course
    {
        return Course::where('active', true)->whereHas('editions', fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled']))->findOrFail($id);
    }

    protected function edition(string $token): CourseEdition
    {
        return CourseEdition::where('public_token', $token)->whereNotIn('status', ['draft', 'cancelled'])
            ->whereHas('course', fn ($q) => $q->where('active', true))->firstOrFail();
    }

    protected function page(Request $request, Course $course, ?CourseEdition $edition = null, array $errors = [], int $status = 200)
    {
        $editions = $course->editions()->whereNotIn('status', ['draft', 'cancelled'])
            ->when($edition, fn ($q) => $q->whereKey($edition->id))
            ->with(['modules' => fn ($q) => $q->orderBy('display_order'), 'editionInstructors.instructor'])
            ->withCount(['registrations as occupied' => fn ($q) => $q->where('status', 'registered')])->orderBy('start_date')->get();
        $data = [
            'course' => $course->only(['id', 'name', 'activity', 'description', 'objective', 'target_audience', 'duration_hours', 'modality']),
            'editions' => $editions->map(function ($item) {
                return array_merge($item->only(['id', 'location', 'status', 'max_capacity']), [
                    'start_date' => $item->start_date->format('d/m/Y'),
                    'end_date' => $item->end_date?->format('d/m/Y'),
                    'start_time' => $item->start_time ? substr($item->start_time, 0, 5) : null,
                    'end_time' => $item->end_time ? substr($item->end_time, 0, 5) : null,
                    'can_register' => $item->status === 'registration_open'
                        && (! $item->registration_start || now()->gte($item->registration_start))
                        && (! $item->registration_end || now()->lte($item->registration_end))
                        && ($item->max_capacity === null || $item->occupied < $item->max_capacity),
                    'modules' => $item->modules->map(fn ($module) => $module->only(['id', 'name', 'description'])),
                    'instructors' => $item->editionInstructors->map(fn ($assignment) => [
                        'name' => $assignment->instructor->name, 'module_id' => $assignment->module_id,
                    ]),
                ]);
            }),
            'dependencies' => Dependency::where('active', true)->orderBy('name')->get(['id', 'name', 'acronym']),
        ];
        if ($request->expectsJson()) {
            return response()->json($errors ? ['error' => ['message' => 'Revisa los datos.', 'details' => $errors]] : ['data' => $data], $status);
        }

        return response()->view('public-courses.show', array_merge($data, [
            'formErrors' => $errors,
            'input' => $request->only(['edition_id', 'dependency_id', 'first_name', 'first_last_name', 'second_last_name', 'curp', 'institutional_email', 'phone']),
            'action' => $edition ? '/c/'.$edition->public_token.'/inscripciones' : '/cursos/'.$course->id.'/inscripciones',
        ]), $status)->header('Cache-Control', 'private, no-store');
    }

    protected function submit(Request $request, Course $course, ?CourseEdition $edition = null)
    {
        $input = $request->all();
        if (isset($input['curp']) && is_string($input['curp'])) {
            $input['curp'] = strtoupper(trim($input['curp']));
        }
        // El token decide la edicion, aunque el cliente envie otro edition_id.
        $input['edition_id'] = $edition?->id ?? ($input['edition_id'] ?? null);
        $validator = Validator::make($input, [
            'edition_id' => 'required|integer|min:1',
            'dependency_id' => 'required|integer|exists:dependencies,id',
            'first_name' => 'required|string|max:100',
            'first_last_name' => 'required|string|max:100',
            'second_last_name' => 'nullable|string|max:100',
            'curp' => 'required|string|size:18',
            'institutional_email' => 'required|email|max:180',
            'phone' => 'nullable|string|max:30',
        ]);
        if ($validator->fails()) {
            return $this->page($request, $course, $edition, $validator->errors()->all(), 422);
        }
        $data = $validator->validated();
        $selected = $course->editions()->find($data['edition_id']);
        if (! $selected) {
            return $this->page($request, $course, $edition, ['Selecciona una edición de este curso.'], 422);
        }
        unset($data['edition_id']);
        try {
            $registration = app(PublicRegistrationService::class)->register($selected, $data);
        } catch (HttpResponseException $exception) {
            if ($request->expectsJson()) {
                return $exception->getResponse();
            }
            $error = json_decode($exception->getResponse()->getContent(), true);

            return $this->page($request, $course, $edition, [$error['error']['message']], $exception->getResponse()->getStatusCode());
        } catch (QueryException $exception) {
            if (! str_starts_with((string) ($exception->errorInfo[0] ?? ''), '23')) {
                throw $exception;
            }

            return $this->page($request, $course, $edition, ['Los datos entraron en conflicto con otro registro. Revisa si ya estás inscrito.'], 409);
        }
        $svg = app(QrCodeService::class)->download($registration->qr_token, 'qr.svg')->getContent();
        $confirmation = ['folio' => $registration->folio, 'qr_svg' => $svg, 'email' => 'not_configured'];
        if ($request->expectsJson()) {
            return response()->json(['data' => $confirmation], 201)->header('Cache-Control', 'private, no-store');
        }

        //start ENVIO DE CORREO
        try {
            $registration->loadMissing([
                'participant',
                'courseEdition.course',
            ]);

            $qrPng = app(QrCodeService::class)->png($registration->qr_token);
            Mail::to($registration->participant->institutional_email)
                ->send(new RegistrationConfirmationMail($registration, $qrPng));

            $confirmation['email'] = 'sent';

        } catch (\Throwable $e) {
            Log::error('Error enviando correo de inscripción SIGECAP', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            $confirmation['email'] = 'failed';
        }
        // end ENVIO DE CORREO
        return response()->view('public-courses.confirmation', $confirmation + ['course' => $course], 201)
            ->header('Cache-Control', 'private, no-store');
    }
}
