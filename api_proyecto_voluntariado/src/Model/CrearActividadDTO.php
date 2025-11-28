<?php

namespace App\Model;

class CrearActividadDTO
{
    public function __construct(
        public string $cifOrganizacion, // Necesitamos saber QUIÉN crea la actividad
        public string $nombre,
        public ?string $descripcion = null,
        public ?string $sector = null,
        public ?string $zona = null,
        public ?string $fechaInicio = null, // "YYYY-MM-DD"
        public array $ods = [], // ["Salud", "Educación"]
        public array $habilidades = []
    ) {}
}