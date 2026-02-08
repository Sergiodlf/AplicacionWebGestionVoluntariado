<?php

namespace App\Enum;

enum ActivityApproval: string
{
    case PENDIENTE = 'PENDIENTE';
    case ACEPTADA = 'ACEPTADA';
    case RECHAZADA = 'RECHAZADA';

    public static function isValid(string $status): bool
    {
        return in_array(strtoupper($status), array_column(self::cases(), 'value'));
    }
}
