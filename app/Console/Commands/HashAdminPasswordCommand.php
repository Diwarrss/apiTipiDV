<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

final class HashAdminPasswordCommand extends Command
{
    protected $signature = 'admin:hash-password {password : Contraseña en texto plano}';

    protected $description = 'Genera un hash bcrypt para ADMIN_PASSWORD_HASH en .env';

    public function handle(): int
    {
        $hash = Hash::make($this->argument('password'));
        $this->line($hash);
        $this->newLine();
        $this->info('Copia en .env: ADMIN_PASSWORD_HASH="'.$hash.'"');

        return self::SUCCESS;
    }
}
