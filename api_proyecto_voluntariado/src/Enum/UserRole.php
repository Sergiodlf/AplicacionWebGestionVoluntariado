<?php

namespace App\Enum;

enum UserRole: string
{
    case ADMIN = 'ROLE_ADMIN';
    case ORGANIZACION = 'ROLE_ORGANIZACION';
    case VOLUNTARIO = 'ROLE_VOLUNTARIO';
}
