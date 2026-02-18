<?php

namespace App\Controller;

use App\Model\ApiErrorResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

trait ApiErrorTrait
{
    /**
     * Genera una JsonResponse de error con estructura unificada.
     *
     * @param string $message  Mensaje descriptivo
     * @param int    $code     Código HTTP
     * @param mixed  $details  Detalles adicionales (opcional)
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $code, mixed $details = null): JsonResponse
    {
        return new JsonResponse(
            ApiErrorResponse::create($message, $code, $details),
            $code
        );
    }
}
