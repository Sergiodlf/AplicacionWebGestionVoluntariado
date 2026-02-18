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
    public function getODSById(int $id): ?ODS;
    
    public function getAllHabilidades(): array;
    public function getHabilidadById(int $id): ?Habilidad;
    
    public function getAllIntereses(): array;
    public function getInteresById(int $id): ?Interes;
    
    public function getAllNecesidades(): array;
    public function getNecesidadById(int $id): ?Necesidad;
    
    public function getAllCiclos(): array;

    public function createODS(string $nombre): ODS;
    public function deleteODS(int $id): bool;
    
    public function createHabilidad(string $nombre): Habilidad;
    public function deleteHabilidad(int $id): bool;
    
    public function createInteres(string $nombre): Interes;
    public function deleteInteres(int $id): bool;
    
    public function createNecesidad(string $nombre): Necesidad;
    public function deleteNecesidad(int $id): bool;
}
