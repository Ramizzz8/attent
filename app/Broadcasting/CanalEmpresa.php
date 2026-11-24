<?php

namespace App\Broadcasting;

use App\Models\User;

/** Canal privado "empresa.{id}": solo los empleados de esa empresa reciben sus avisos. */
class CanalEmpresa
{
    public function join(User $user, int|string $empresaId): bool
    {
        return (int) $user->empresa_id === (int) $empresaId;
    }
}
