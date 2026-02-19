<?php

namespace App\Enum;

enum ActivityStatus: string
{
    case PENDIENTE = 'PENDIENTE';
    case EN_CURSO = 'EN_CURSO';
    case COMPLETADA = 'COMPLETADA';
    case CANCELADA = 'CANCELADA';
    
    // Mapping for legacy/uppercase inputs if needed
    public static function fromLegacy(string $status): ?self
    {
        return match (strtoupper($status)) {
            'SIN COMENZAR', 'PENDIENTE' => self::PENDIENTE,
            'EN CURSO', 'EN_CURSO', 'ABIERTA' => self::EN_CURSO,
            'COMPLETADA', 'COMPLETADO', 'FINALIZADO' => self::COMPLETADA,
            'CANCELADO', 'CANCELADA' => self::CANCELADA,
            default => null,
        };
    }
}
