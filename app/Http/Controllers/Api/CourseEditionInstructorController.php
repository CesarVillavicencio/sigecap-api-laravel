<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseEditionInstructor;
use App\Models\CourseEditionModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CourseEditionInstructorController extends CrudController
{
    protected string $model = CourseEditionInstructor::class;

    protected array $filters = ['course_edition_id', 'instructor_id'];

    protected function rules(?Model $record): array
    {
        return [
            'course_edition_id' => 'required|integer|min:1|exists:course_editions,id',
            'instructor_id' => 'required|integer|min:1|exists:instructors,id',
            'module_id' => 'sometimes|nullable|integer|min:1|exists:course_edition_modules,id',
            'topic' => 'sometimes|nullable|string|max:250',
            'display_order' => 'sometimes|integer|min:1|max:2147483647',
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $record): array
    {
        $values = array_replace($record?->getAttributes() ?? [], $data);
        if (isset($values['module_id'])) {
            $module = CourseEditionModule::lockForUpdate()->find($values['module_id']);
            if (! $module || (string) $module->course_edition_id !== (string) $values['course_edition_id']) {
                $this->fail('INVALID_REFERENCE', 'El modulo debe pertenecer a la misma edicion.');
            }
        }
        $duplicate = CourseEditionInstructor::where('course_edition_id', $values['course_edition_id'])
            ->where('instructor_id', $values['instructor_id'])
            ->where('module_id', $values['module_id'] ?? null);
        if ($record) {
            $duplicate->where('id', '!=', $record->getKey());
        }
        if ($duplicate->exists()) {
            $this->fail('DUPLICATE_RESOURCE', 'El instructor ya esta asignado a ese modulo de la edicion.');
        }

        return $data;
    }
}
