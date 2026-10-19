<?php

namespace App\Http\Controllers\Api;

use App\Models\Dependency;
use Illuminate\Database\Eloquent\Model;

class DependencyController extends CrudController
{
    protected string $model = Dependency::class;

    protected array $filters = ['active'];

    protected function rules(?Model $record): array
    {
        return [
            'name' => 'required|string|max:200',
            'acronym' => 'sometimes|nullable|string|max:50',
            'active' => 'sometimes|boolean',
        ];
    }

    protected function remove(Model $record): void
    {
        $record->update(['active' => false]);
    }
}
