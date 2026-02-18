<?php

namespace App\Enum;

enum InscriptionStatus: string
{
    case PENDIENTE = 'PENDIENTE';
    case CONFIRMADO = 'CONFIRMADO';
    case RECHAZADO = 'RECHAZADO';
    case EN_CURSO = 'EN_CURSO';
    case FINALIZADO = 'FINALIZADO';
    case COMPLETADA = 'COMPLETADA'; 
    case CANCELADA = 'CANCELADA';
    
    // Alias for compatibility if needed, though uniform usage is preferred
    public const ACEPTADO = self::CONFIRMADO; 
}
