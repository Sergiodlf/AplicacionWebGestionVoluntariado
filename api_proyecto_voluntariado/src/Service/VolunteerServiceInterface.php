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
    
    public function updateProfile(Voluntario $voluntario, array $data): Voluntario;
    public function updateStatus(Voluntario $voluntario, string|\App\Enum\VolunteerStatus $status): void;
    
    public function countByStatus(string|\App\Enum\VolunteerStatus $status): int;
}
