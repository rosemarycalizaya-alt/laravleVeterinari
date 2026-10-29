<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use PDOException;

class CreateHuellitaDatabase extends Command
{
    protected $signature = 'huellita:database';

    protected $description = 'Crea la base MySQL huellita si no existe, sin modificar ni eliminar datos';

    public function handle(): int
    {
        $connection = config('database.connections.mysql');
        try {
            $pdo = new PDO('mysql:host='.$connection['host'].';port='.$connection['port'].';charset=utf8mb4', $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS huellita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (PDOException $error) {
            $this->error('No se pudo preparar MySQL. Comprueba el servicio, DB_HOST, DB_PORT y las credenciales del entorno. Código: '.$error->getCode());

            return self::FAILURE;
        }
        $this->info('Base huellita disponible. Configura DB_CONNECTION=mysql y DB_DATABASE=huellita en .env; después ejecuta php artisan migrate.');

        return self::SUCCESS;
    }
}
