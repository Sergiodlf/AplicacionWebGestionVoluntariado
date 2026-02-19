<?php

namespace App\Enum;

enum VolunteerStatus: string
{
    case PENDIENTE = 'PENDIENTE';
    case ACEPTADO = 'ACEPTADO';
    case RECHAZADO = 'RECHAZADO';
}
