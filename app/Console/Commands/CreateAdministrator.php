<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdministrator extends Command
{
    protected $signature = 'huellita:admin {email? : Correo del primer administrador} {--name= : Nombre del administrador} {--generate : Genera una contraseña aleatoria solo en local/testing}';

    protected $description = 'Crea el primer administrador solicitando una contraseña oculta, sin contraseñas predeterminadas';

    public function handle(): int
    {
        if (User::where('rol', 'administrador')->where('activo', true)->exists()) {
            $this->error('Ya existe un administrador activo. Crea otras cuentas desde Personal y permisos.');

            return self::FAILURE;
        }
        $generate = (bool) $this->option('generate');
        if ($generate && ! app()->environment(['local', 'testing'])) {
            $this->error('La generación automática solo está disponible en local o testing.');

            return self::FAILURE;
        }
        if (! $generate && ! $this->input->isInteractive()) {
            $this->error('Ejecuta este comando en una terminal interactiva para introducir la contraseña de forma segura.');

            return self::FAILURE;
        }
        $email = $this->argument('email') ?: $this->ask('Correo electrónico');
        $name = $this->option('name') ?: $this->ask('Nombre completo');
        $password = $generate ? Str::password(24) : $this->secret('Contraseña (mínimo 12 caracteres)');
        $confirmation = $generate ? $password : $this->secret('Repite la contraseña');
        $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], ['name' => 'required|string|max:150', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:12|confirmed']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = User::create(['name' => $name, 'email' => $email, 'password' => $password, 'rol' => 'administrador', 'activo' => true]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->info('Administrador creado. Inicia sesión con el correo y la contraseña que acabas de elegir.');
        if ($generate) {
            $this->line('Correo: '.$email);
            $this->line('Contraseña local generada: '.$password);
            $this->warn('Guárdala de forma segura. No se volverá a mostrar ni se guardará en archivos.');
        }

        return self::SUCCESS;
    }
}
