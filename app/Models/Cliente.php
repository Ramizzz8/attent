<?php

namespace App\Models;

use App\Tenancy\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Cliente externo de una empresa (conductor, vecino, usuario de un equipo).
 * Se autentica con el guard "cliente"; la contraseña es opcional.
 */
class Cliente extends Authenticatable
{
    use HasFactory, Notifiable, PerteneceAEmpresa;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verificado_en' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
