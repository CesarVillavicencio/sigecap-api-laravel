<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use App\Models\Registration;
use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CourseController extends CrudController
{
    protected string $model = Course::class;

    protected array $filters = ['active'];

    protected function rules(?Model $record): array
    {
        return [
            'name' => 'required|string|max:250',
            'activity' => 'sometimes|nullable|string|max:100',
            'description' => 'sometimes|nullable|string|max:20000',
            'objective' => 'sometimes|nullable|string|max:20000',
            'target_audience' => 'sometimes|nullable|string|max:20000',
            'duration_hours' => 'sometimes|nullable|numeric|gt:0|max:9999.99|decimal:0,2',
            'modality' => 'sometimes|nullable|string|max:50',
            'active' => 'sometimes|boolean',
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $record): array
    {
        return $record ? $data : array_merge($data, $this->identity($request, 'created_by'));
    }

    protected function remove(Model $record): void
    {
        $editionIds = $record->editions()->lockForUpdate()->pluck('id');
        if (Registration::whereIn('course_edition_id', $editionIds)->exists()) {
            $this->fail('COURSE_HAS_REGISTRATIONS', 'El curso tiene inscripciones; utiliza active: false.');
        }
        $record->editions()->delete();
        $record->delete();
    }

    public function qr(string $id, QrCodeService $qr): Response
    {
        $course = $this->find($id);

        return $qr->download($qr->publicUrl('cursos/'.$course->id), 'curso-'.$course->id.'.svg');
    }
}
