<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    public function test(): JsonResponse
    {
        // TODO: Implementar esta operacion.
        return response()->json([
            'error' => [
                'code' => 'NOT_IMPLEMENTED',
                'message' => 'Operacion pendiente de implementar.',
            ],
        ], 501);
    }

    public function pdf(): JsonResponse
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
