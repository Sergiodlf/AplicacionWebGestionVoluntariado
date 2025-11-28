<?php

namespace App\Model;

class RegistroOrganizacionDTO
{
    public function __construct(
        public string $nombre,
        public string $cif,
        public string $email,
        public string $password,
        public ?string $sector = null,
        public ?string $zona = null, // En tu DB es 'localidad'
        public ?string $descripcion = null
    ) {}
}