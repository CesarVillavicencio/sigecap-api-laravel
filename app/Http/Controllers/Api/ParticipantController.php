<?php

namespace App\Http\Controllers\Api;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ParticipantController extends CrudController
{
    protected string $model = Participant::class;

    protected array $filters = ['active', 'dependency_id'];

    protected function rules(?Model $record): array
    {
        return [
            'dependency_id' => 'nullable|required_without:dependency_name|integer|min:1|exists:dependencies,id',
            'dependency_name' => 'nullable|required_without:dependency_id|string|max:200',
            'first_name' => 'required|string|max:100',
            'first_last_name' => 'required|string|max:100',
            'second_last_name' => 'sometimes|nullable|string|max:100',
            'curp' => ['required', 'string', 'size:18', Rule::unique('participants', 'curp')->ignore($record?->getKey())],
            'area' => 'sometimes|nullable|string|max:150',
            'position' => 'sometimes|nullable|string|max:150',
            'institutional_email' => 'required|email|max:180',
            'phone' => 'sometimes|nullable|string|max:30',
            'active' => 'sometimes|boolean',
        ];
    }

    protected function normalize(array $input): array
    {
        if (isset($input['curp']) && is_string($input['curp'])) {
            $input['curp'] = strtoupper(trim($input['curp']));
        }

        return $input;
    }

    protected function remove(Model $record): void
    {
        $record->update(['active' => false]);
    }
}
