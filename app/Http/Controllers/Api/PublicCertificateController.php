<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PublicCertificateController extends Controller
{
    public function verify(string $token): JsonResponse
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
