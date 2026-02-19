<?php

namespace App\Service;

use App\Entity\ODS;
use App\Entity\Habilidad;
use App\Entity\Interes;
use App\Entity\Necesidad;

/**
 * Interfaz para el servicio de categorías.
 */
interface CategoryServiceInterface
{
    public function getAllODS(): array;
    // public function getODSById(int $id): ?ODS; // Not implemented
    
    public function getAllHabilidades(): array;
    // public function getHabilidadById(int $id): ?Habilidad; // Not implemented
    
    public function getAllIntereses(): array;
    // public function getInteresById(int $id): ?Interes; // Not implemented
    
    public function getAllNecesidades(): array;
    // public function getNecesidadById(int $id): ?Necesidad; // Not implemented
    
    public function getAllCiclos(): array;

    // public function createODS(string $nombre): ODS; // Not implemented
    // public function deleteODS(int $id): bool; // Not implemented
    
    public function createHabilidad(string $nombre): int;
    public function deleteHabilidad(int $id): bool;
    
    public function createInteres(string $nombre): int;
    public function deleteInteres(int $id): bool;
    
    // public function createNecesidad(string $nombre): Necesidad; // Not implemented
    // public function deleteNecesidad(int $id): bool; // Not implemented
}
