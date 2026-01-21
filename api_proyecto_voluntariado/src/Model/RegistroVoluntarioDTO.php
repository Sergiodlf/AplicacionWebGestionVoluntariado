<?php

namespace App\Model;

class RegistroVoluntarioDTO
{
    public ?string $dni = null;
    public ?string $nombre = null;
    public ?string $email = null;
    public ?string $password = null;
    
    public ?string $fechaNacimiento = null;
    public ?string $zona = null;
    public ?string $experiencia = null;
    
    // CORRECCIÓN: Quitamos '= false' para que Symfony no fuerce que sea booleano.
    // Al ponerlo como null, aceptará string ("si"), int (1) o bool (true).
    public $coche = null;
    
    public array $idiomas = [];
    public array $habilidades = [];
    public array $intereses = [];
    public array $disponibilidad = [];
    
    /** @var string|array|null */
    public $ciclo = null;
}