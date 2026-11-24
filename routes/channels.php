<?php

use App\Broadcasting\CanalEmpresa;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('empresa.{empresaId}', CanalEmpresa::class);
