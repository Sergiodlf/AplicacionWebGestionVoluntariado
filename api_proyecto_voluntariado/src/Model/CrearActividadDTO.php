<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class CrearActividadDTO
{
    public function __construct(
        public ?string $cifOrganizacion = null,

        #[Assert\NotBlank(message: "El nombre es obligatorio")]
        #[Assert\Length(max: 40, maxMessage: "El nombre no puede superar los 40 caracteres")]
        public string $nombre,

        public ?string $descripcion = null,
        public ?string $sector = null,
        public ?string $zona = null,

        #[Assert\Date(message: "La fecha de inicio debe ser una fecha válida (YYYY-MM-DD)")]
        public ?string $fechaInicio = null,

        #[Assert\Date(message: "La fecha de fin debe ser una fecha válida (YYYY-MM-DD)")]
        public ?string $fechaFin = null,

        #[Assert\GreaterThan(0, message: "El cupo máximo debe ser mayor que cero")]
        public ?int $maxParticipantes = null,

        #[Assert\Length(max: 40, maxMessage: "La dirección no puede superar los 40 caracteres")]
        public ?string $direccion = null,

        public array $ods = [],
        public array $habilidades = []
    ) {}
}