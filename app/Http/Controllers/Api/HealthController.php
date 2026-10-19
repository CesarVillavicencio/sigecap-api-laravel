<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // TODO: Implementar esta operacion.
        return response()->json([
            'error' => [
                'code' => 'NOT_IMPLEMENTED',
                'message' => 'Operacion pendiente de implementar.',
            ],
        ], 501);
    }

    public function ready(): JsonResponse
    {
        // TODO: Implementar esta operacion.
        return response()->json([
            'error' => [
                'code' => 'NOT_IMPLEMENTED',
                'message' => 'Operacion pendiente de implementar.',
            ],
        ], 501);
    }
}
