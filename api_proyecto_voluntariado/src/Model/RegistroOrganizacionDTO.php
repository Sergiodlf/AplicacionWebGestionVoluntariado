<?php

namespace App\Model;

class RegistroOrganizacionDTO
{
    public string $cif;
    public string $nombre;
    public string $email;
    public string $password;
    
    // CAMPOS OBLIGATORIOS (Según tu tabla SQL Server son NOT NULL)
    // Si no los pones aquí, el JSON no los leerá y dará error de NULL en la BD.
    public string $direccion;
    public string $localidad;
    public string $cp;
    public string $descripcion;
    public string $contacto;
    
    // Campo opcional (puede ser null)
    public ?string $sector = null;
}