<?php

namespace App\Enum;

enum OrganizationStatus: string
{
    case PENDIENTE = 'pendiente';
    case APROBADO = 'aprobado';
    case RECHAZADO = 'rechazado';
}
