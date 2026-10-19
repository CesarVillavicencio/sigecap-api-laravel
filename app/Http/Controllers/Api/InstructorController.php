<?php

namespace App\Http\Controllers\Api;

use App\Models\Instructor;
use Illuminate\Database\Eloquent\Model;

class InstructorController extends CrudController
{
    protected string $model = Instructor::class;

    protected array $filters = ['active'];

    protected function rules(?Model $record): array
    {
        return [
            'name' => 'required|string|max:200',
            'position' => 'sometimes|nullable|string|max:200',
            'institution' => 'sometimes|nullable|string|max:200',
            'email' => 'sometimes|nullable|email|max:180',
            'phone' => 'sometimes|nullable|string|max:30',
            'active' => 'sometimes|boolean',
        ];
    }

    protected function remove(Model $record): void
    {
        if ($record->editionAssignments()->exists()) {
            $this->fail('INSTRUCTOR_HAS_ASSIGNMENTS', 'Retira las asignaciones o desactiva al instructor.');
        }
        $record->delete();
    }
}
