<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class RegistroOrganizacionDTO
{
    #[Assert\NotBlank(message: "El CIF es obligatorio")]
    #[Assert\Regex(pattern: "/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/", message: "Formato de CIF inválido")]
    public string $cif;

    #[Assert\NotBlank(message: "El nombre es obligatorio")]
    #[Assert\Length(max: 255)]
    public string $nombre;

    #[Assert\NotBlank(message: "El email es obligatorio")]
    #[Assert\Email(message: "Email inválido")]
    public string $email;

    #[Assert\NotBlank(message: "La contraseña es obligatoria")]
    #[Assert\Length(min: 6, minMessage: "La contraseña debe tener al menos 6 caracteres")]
    public string $password;
    
    #[Assert\NotBlank(message: "La dirección es obligatoria")]
    public string $direccion;

    #[Assert\NotBlank(message: "La localidad es obligatoria")]
    public string $localidad;

    #[Assert\NotBlank(message: "El código postal es obligatorio")]
    #[Assert\Regex(pattern: "/^[0-9]{5}$/", message: "Código postal debe ser de 5 dígitos")]
    public string $cp;

    #[Assert\NotBlank(message: "La descripción es obligatoria")]
    public string $descripcion;

    #[Assert\NotBlank(message: "El contacto es obligatorio")]
    public string $contacto;
    
    public ?string $sector = null;
}