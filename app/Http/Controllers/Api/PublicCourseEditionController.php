<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCourseEditionController extends Controller
{
    public function show(string $public_token): JsonResponse
    {
        // TODO: Implementar esta operacion.
        return response()->json([
            'error' => [
                'code' => 'NOT_IMPLEMENTED',
                'message' => 'Operacion pendiente de implementar.',
            ],
        ], 501);
    }

    public function register(Request $request, string $public_token): JsonResponse
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
