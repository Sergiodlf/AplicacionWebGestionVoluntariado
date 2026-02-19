<?php

namespace App\Service;

use App\Model\RegistroOrganizacionDTO;
use App\Entity\Organizacion;
use App\Enum\OrganizationStatus;

/**
 * Interfaz para el servicio de organizaciones.
 */
interface OrganizationServiceInterface
{
    public function registerOrganization(RegistroOrganizacionDTO $dto, bool $isAdmin = false): Organizacion;
    public function checkDuplicates(string $cif, string $email): ?string;
    public function validateDTO(RegistroOrganizacionDTO $dto): ?string;
    public function getByCif(string $cif): ?Organizacion;
    public function getByEmail(string $email): ?Organizacion;
    public function getAll(array $criteria = []): array;
    public function updateState(string $cif, string|OrganizationStatus $status): ?Organizacion;
    public function updateOrganization(string $cif, array $data): ?Organizacion;
    public function updateProfile(Organizacion $org, array $data): Organizacion;
    public function deleteOrganization(string $cif): bool;
    public function countByStatus(string|OrganizationStatus $status): int;
}
