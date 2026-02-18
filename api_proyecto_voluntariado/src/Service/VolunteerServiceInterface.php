<?php

namespace App\Service;

use App\Model\RegistroVoluntarioDTO;
use App\Entity\Voluntario;

/**
 * Interfaz para el servicio de voluntarios.
 */
interface VolunteerServiceInterface
{
    public function registerVolunteer(RegistroVoluntarioDTO $dto, bool $isAdmin = false): Voluntario;
    public function checkDuplicates(string $dni, string $email): ?string;
    public function validateDTO(RegistroVoluntarioDTO $dto): ?string;
    public function getById(string $dni): ?Voluntario;
    public function getByEmail(string $email): ?Voluntario;
    public function getAll(array $criteria = []): array;
    public function updateProfile(Voluntario $voluntario, array $data): void;
    public function updateState(string $dni, \App\Enum\VolunteerStatus $status): ?Voluntario;
    public function countByStatus(\App\Enum\VolunteerStatus $status): int;
}
