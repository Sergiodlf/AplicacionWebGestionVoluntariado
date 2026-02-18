<?php

namespace App\Model;

class ApiErrorResponse
{
    /**
     * Crea un array con la estructura de error unificada.
     *
     * @param string $message  Mensaje descriptivo del error
     * @param int    $code     Código HTTP
     * @param mixed  $details  Detalles adicionales (errores de validación, etc.)
     * @return array
     */
    public static function create(string $message, int $code, mixed $details = null): array
    {
        $response = [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        return $response;
    }
}
