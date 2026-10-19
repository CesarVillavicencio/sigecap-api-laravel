<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseEditionModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CourseEditionModuleController extends CrudController
{
    protected string $model = CourseEditionModule::class;

    protected array $filters = ['course_edition_id'];

    protected function rules(?Model $record): array
    {
        return [
            'course_edition_id' => 'required|integer|min:1|exists:course_editions,id',
            'name' => 'required|string|max:200',
            'description' => 'sometimes|nullable|string|max:20000',
            'display_order' => 'sometimes|integer|min:1|max:2147483647',
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $record): array
    {
        if ($record && isset($data['course_edition_id']) && (string) $data['course_edition_id'] !== (string) $record->course_edition_id && $record->instructorAssignments()->exists()) {
            $this->fail('MODULE_HAS_ASSIGNMENTS', 'Retira las asignaciones antes de mover el modulo a otra edicion.');
        }

        return $data;
    }
}
