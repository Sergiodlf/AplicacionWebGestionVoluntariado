<?php

namespace App\Enum;

enum ActivityStatus: string
{
    case PENDIENTE = 'Sin comenzar';
    case EN_CURSO = 'En curso';
    case COMPLETADA = 'Completada';
    case CANCELADA = 'CANCELADO';
    
    // Mapping for legacy/uppercase inputs if needed
    public static function fromLegacy(string $status): ?self
    {
        return match (strtoupper($status)) {
            'SIN COMENZAR', 'PENDIENTE' => self::PENDIENTE,
            'EN CURSO', 'ABIERTA' => self::EN_CURSO,
            'COMPLETADA', 'COMPLETADO', 'FINALIZADO' => self::COMPLETADA,
            'CANCELADO', 'CANCELADA' => self::CANCELADA,
            default => null,
        };
    }
}
