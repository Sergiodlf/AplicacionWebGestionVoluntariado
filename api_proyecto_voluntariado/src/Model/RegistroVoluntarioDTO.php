<?php

namespace App\Model;

class RegistroVoluntarioDTO
{
    public ?string $dni = null;
    public ?string $nombreCompleto = null;
    public ?string $correo = null;
    public ?string $password = null;
    public ?string $fechaNacimiento = null;
    public ?string $zona = null;
    public ?string $ciclo = null;
    public ?string $experiencia = null;
    
    // Puede venir como string "si"/"no" o boolean, lo tratamos en el controller
    public mixed $coche = null;

    public array $idiomas = [];
    public array $habilidades = [];
    public array $intereses = [];
}