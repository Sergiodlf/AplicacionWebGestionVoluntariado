<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class RegistroVoluntarioDTO
{
    #[Assert\NotBlank(message: "El DNI es obligatorio")]
    #[Assert\Regex(pattern: "/^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i", message: "Formato de DNI inválido")]
    public ?string $dni = null;

    #[Assert\NotBlank(message: "El nombre es obligatorio")]
    #[Assert\Length(max: 100)]
    public ?string $nombre = null;

    #[Assert\NotBlank(message: "El email es obligatorio")]
    #[Assert\Email(message: "Email inválido")]
    public ?string $email = null;

    #[Assert\NotBlank(message: "La contraseña es obligatoria")]
    #[Assert\Length(min: 6, minMessage: "La contraseña debe tener al menos 6 caracteres")]
    public ?string $password = null;
    
    #[Assert\NotBlank(message: "La fecha de nacimiento es obligatoria")]
    #[Assert\Date(message: "Fecha de nacimiento inválida (YYYY-MM-DD)")]
    public ?string $fechaNacimiento = null;

    public ?string $zona = null;
    public ?string $experiencia = null;
    
    public $coche = null;
    
    public array $idiomas = [];
    public array $habilidades = [];
    public array $intereses = [];
    public array $disponibilidad = [];
    
    /** @var string|array|null */
    public $ciclo = null;
}