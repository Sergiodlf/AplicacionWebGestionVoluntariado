<?php

namespace App\Model;

class RegistroVoluntarioDTO
{
    public string $dni;
    public string $nombreCompleto;
    public string $correo;
    public string $password;
    
    public ?string $fechaNacimiento = null;
    public ?string $zona = null;
    public ?string $experiencia = null;
    
    // CORRECCIÓN: Quitamos '= false' para que Symfony no fuerce que sea booleano.
    // Al ponerlo como null, aceptará string ("si"), int (1) o bool (true).
    public $coche = null;
    
    public array $idiomas = [];
    public array $habilidades = [];
    public array $intereses = [];
    
    public ?string $ciclo = null;
}