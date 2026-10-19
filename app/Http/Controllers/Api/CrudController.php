<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

abstract class CrudController extends Controller
{
    protected string $model;

    protected array $filters = [];

    abstract protected function rules(?Model $record): array;

    public function index(Request $request): JsonResponse
    {
        $rules = ['page' => 'sometimes|integer|min:1|max:1000000', 'limit' => 'sometimes|integer|min:1|max:100'];
        foreach ($this->filters as $field) {
            $rules[$field] = match (true) {
                $field === 'active' => 'sometimes|in:true,false',
                str_ends_with($field, '_id'), $field === 'registered_by' => 'sometimes|integer|min:1|max:9223372036854775807',
                default => 'sometimes|string|max:100',
            };
        }
        $data = $this->validateInput($request->query(), $rules);
        $page = (int) ($data['page'] ?? 1);
        $limit = (int) ($data['limit'] ?? 20);
        $query = $this->model::query();
        foreach ($this->filters as $field) {
            if (array_key_exists($field, $data)) {
                $query->where($field, $field === 'active' ? $data[$field] === 'true' : $data[$field]);
            }
        }

        return response()->json([
            'data' => $query->orderBy('id')->offset(($page - 1) * $limit)->limit($limit)->get(),
            'pagination' => compact('page', 'limit'),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => $this->find($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->mutate(function () use ($request) {
            $data = $this->validated($request, null);
            $record = $this->model::create($this->prepare($request, $data, null));

            return response()->json(['data' => $record->refresh()], 201)
                ->header('Location', rtrim($request->url(), '/').'/'.$record->getKey());
        });
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return $this->mutate(function () use ($request, $id) {
            $record = $this->find($id, true);
            $data = $this->validated($request, $record);
            $record->fill($this->prepare($request, $data, $record))->save();

            return response()->json(['data' => $record->refresh()]);
        });
    }

    public function destroy(string $id): Response
    {
        return $this->mutate(function () use ($id) {
            $this->remove($this->find($id, true));

            return response()->noContent();
        });
    }

    protected function find(string $id, bool $lock = false): Model
    {
        $query = $this->model::query();
        $record = ($lock ? $query->lockForUpdate() : $query)->find($id);
        if (! $record) {
            $this->fail('NOT_FOUND', 'El registro no existe.', 404);
        }

        return $record;
    }

    protected function validated(Request $request, ?Model $record): array
    {
        $input = $this->normalize($request->all());
        $rules = $this->rules($record);
        $unknown = array_diff(array_keys($input), array_keys($rules));
        if ($unknown) {
            $this->fail('VALIDATION_ERROR', 'Hay campos desconocidos o no editables.', 422, array_values($unknown));
        }
        if ($record && ! $input) {
            $this->fail('VALIDATION_ERROR', 'Envia al menos un campo editable.', 422);
        }
        // Validar el resultado completo permite PATCH parciales y reglas entre campos.
        $existing = $record ? array_intersect_key($record->getAttributes(), $rules) : [];
        foreach ($existing as $field => $value) {
            if ($value !== null && in_array($record->getCasts()[$field] ?? null, ['date', 'immutable_date'], true)) {
                $existing[$field] = $record->{$field}->format('Y-m-d');
            }
        }
        $data = $this->validateInput(array_replace($existing, $input), $rules);

        return array_intersect_key($data, $input);
    }

    protected function normalize(array $input): array
    {
        return $input;
    }

    protected function validateInput(array $input, array $rules): array
    {
        if (array_diff(array_keys($input), array_keys($rules))) {
            $this->fail('VALIDATION_ERROR', 'Hay campos desconocidos.', 422);
        }
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $this->fail('VALIDATION_ERROR', 'Parametros invalidos.', 422, $validator->errors()->toArray());
        }

        return $validator->validated();
    }

    protected function prepare(Request $request, array $data, ?Model $record): array
    {
        return $data;
    }

    protected function remove(Model $record): void
    {
        $record->delete();
    }

    protected function identity(Request $request, string $prefix): array
    {
        $identity = $this->validateInput([
            'user' => $request->header('X-SIGBCS-User-Id'),
            'responsibility' => $request->header('X-SIGBCS-Responsibility-Id'),
        ], [
            'user' => 'required|integer|min:1|max:9223372036854775807',
            'responsibility' => 'required|integer|min:1|max:9223372036854775807',
        ]);

        return [$prefix => $identity['user'], $prefix.'_responsibility_id' => $identity['responsibility']];
    }

    protected function mutate(Closure $operation): mixed
    {
        try {
            return DB::transaction($operation, 3);
        } catch (QueryException $exception) {
            $state = (string) ($exception->errorInfo[0] ?? $exception->getCode());
            if (str_starts_with($state, '23')) {
                $this->fail('CONFLICT', 'La operacion entra en conflicto con un registro existente o relacionado.', 409);
            }
            throw $exception;
        }
    }

    protected function fail(string $code, string $message, int $status = 409, array $details = []): never
    {
        throw new HttpResponseException(response()->json([
            'error' => array_filter(compact('code', 'message', 'details')),
        ], $status));
    }
}
