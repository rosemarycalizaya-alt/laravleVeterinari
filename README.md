# Huellita

Aplicación veterinaria en español para propietarios, pacientes y expedientes clínicos digitales. Conserva una historia por mascota y agrega consultas por visita, con medicamentos administrados o recetados, vacunaciones y documentos privados. Incluye portal público, agenda manual y control de llegadas.

## Tecnologías y requisitos

Versiones verificadas durante el desarrollo: PHP 8.5.12, Laravel 13.39.0, Fortify 1.41.0, Livewire 4.4.8, Flux 2.21.0, Pest 5.3.4 y Tailwind CSS 4. La interfaz veterinaria usa MVC tradicional con controladores, Form Requests y Blade; la configuración de cuenta conserva el Livewire del proyecto inicial.

Necesitas PHP 8.5 y sus extensiones habituales (PDO MySQL, PDO SQLite para las pruebas, mbstring, fileinfo, openssl, XML; intl para algunos comandos informativos), Composer, Node.js con npm y MySQL 8+ con InnoDB. No se añadieron dependencias.

## Estado del entorno entregado

El proyecto quedó conectado a la base huellita de MariaDB 10.11.16 (XAMPP), mediante el driver mysql de Laravel. Se comprobó que la base estaba vacía antes de crear las tablas con migraciones. Todas las migraciones se ejecutaron correctamente; no se borraron registros previos. El archivo SQLite anterior se conserva sin eliminarlo.

También existe una base huellita_testing separada para verificar las pruebas y los bloqueos concurrentes sin afectar los datos del panel. El conjunto habitual de pruebas utiliza SQLite en memoria.

## Instalación desde cero

```sh
composer install
npm ci
```

Copia `.env.example` como `.env` si no tienes ya un archivo local. No reemplaces un entorno existente con credenciales o datos importantes.

```sh
php artisan key:generate
```

No regeneres APP_KEY en una instalación ya utilizada. Configura lo siguiente con tus credenciales reales, sin publicarlas:

```dotenv
APP_NAME=Huellita
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_TIMEZONE=America/La_Paz

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=huellita
DB_USERNAME=usuario_de_la_aplicacion
DB_PASSWORD=
```

Para preparar la base sin borrar nada, inicia MySQL y usa una cuenta con permiso CREATE DATABASE:

```sh
php artisan huellita:database
php artisan config:clear
php artisan migrate
php artisan migrate:status
```

El comando de base crea únicamente `huellita` si aún no existe. Todas las tablas se crean con migraciones. Si tu proveedor administra las bases, créala desde su panel y utiliza una cuenta limitada a esa base. Nunca uses `migrate:fresh` sobre información que quieras conservar.

Si decides continuar con SQLite para una demostración local, conserva `DB_CONNECTION=sqlite` y la ruta al archivo existente; no uses ese entorno para certificar los bloqueos de MySQL.

## Primer administrador y acceso seguro

En una instalación nueva:

```sh
php artisan huellita:admin
```

Solicita nombre, correo y una contraseña oculta de al menos 12 caracteres. Almacena únicamente su hash. No hay registro público ni contraseña fija. Si ya existe un administrador activo, crea las demás cuentas desde **Personal y permisos**.

Solo en local/testing puedes generar una contraseña aleatoria que se imprime una vez:

```sh
php artisan huellita:admin admin@huellita.test --name="Administrador Huellita" --generate --no-interaction
```

En este entorno ya se creó esa cuenta local y su contraseña aleatoria se entregó en la conversación. No está escrita en el repositorio. El comando no reemplaza cuentas existentes. Para producción crea otro administrador mediante el modo interactivo, con tu correo real.

## Datos de demostración opcionales

Crea primero tu administrador y después:

```sh
php artisan db:seed --class=DemoSeeder
```

Solo funciona en local/testing. Es idempotente: tres propietarios ficticios, cinco mascotas, expedientes, cuatro consultas, medicamentos de ejemplo, vacunas, agenda y atenciones en distintos estados. Los registros se identifican con DEMO y no contienen pautas farmacológicas reales.

Crea tres cuentas DEMO con contraseñas aleatorias no publicadas. Tu administrador puede asignarles nuevas contraseñas desde Personal y permisos para probar secretaria y veterinario. El seeder no sobrescribe contraseñas existentes. `DatabaseSeeder` no crea usuarios por defecto.

## Abrir la aplicación

```sh
npm run build
php artisan serve
```

Abre el dominio y puerto mostrados por Artisan (por defecto localhost:8000). En desarrollo también puedes ejecutar `npm run dev` en otra terminal o usar `composer run dev`. El login está en `/login`; el panel en `/dashboard`.

## Datos del portal público

Configura en `.env`:

```dotenv
HUELLITA_WHATSAPP=
HUELLITA_TELEFONO=
HUELLITA_CORREO=
HUELLITA_DIRECCION="Dirección del consultorio"
HUELLITA_HORARIOS="Horario confirmado del consultorio"
```

WhatsApp usa código de país y número, sin inventar un contacto real. Cuando falta un número válido, el portal muestra un enlace a contacto y no una conversación falsa. Solo abre `wa.me`; no integra API, reservas, mensajes ni recordatorios automáticos.

## Roles y permisos

| Función | Administrador | Secretaria | Veterinario |
|---|---|---|---|
| Panel, pacientes, agenda y atenciones | Leer | Leer | Leer |
| Propietarios y datos de mascotas | Gestionar | Gestionar | Leer datos del paciente |
| Citas y llegadas | Gestionar | Gestionar | Leer |
| Expedientes, consultas y documentos | Leer | Sin acceso | Leer |
| Antecedentes clínicos, vacunas y documentos | Registrar | Sin acceso | Registrar |
| Consultas y medicamentos de una atención | Registrar en su atención activa | Sin acceso | Registrar en su atención activa |
| Comenzar y finalizar atención | Sí | No | Sí |
| Catálogo de medicamentos | Gestionar | Sin acceso | Gestionar |
| Personal y roles | Gestionar | Sin acceso | Sin acceso |

Los permisos se aplican mediante middleware de roles y autorización en Form Requests. La secretaria no puede acceder a las rutas clínicas. El administrador también ejerce como veterinario y puede registrar información clínica. Solo el profesional asignado puede escribir su consulta activa; al finalizar queda cerrada para preservar el historial.

Las cuentas inactivas no inician sesión y pierden acceso en su siguiente solicitud. Fortify limita intentos de acceso. No se puede degradar al último administrador, desactivar la propia cuenta administrativa ni quitar al profesional de citas o atenciones pendientes. Las cuentas vinculadas a registros no pueden eliminarse desde el perfil.

## Las 11 tablas de negocio

| Tabla | Propósito |
|---|---|
| users | Personal, rol y estado activo |
| propietarios | Familia o responsable del paciente |
| mascotas | Pacientes, propietario y datos generales |
| expedientes_clinicos | Expediente único, código y antecedentes |
| citas | Agenda manual por profesional e intervalo |
| atenciones | Llegadas con o sin cita y estados del consultorio |
| consultas | Un registro clínico por atención |
| medicamentos | Catálogo, sin existencias ni precios |
| medicamentos_consulta | Administración real o receta |
| vacunaciones | Aplicaciones y próxima dosis |
| documentos_clinicos | Metadatos de archivos privados |

Se conservan además tablas técnicas de Laravel para migraciones, sesiones, recuperación, caché y colas. Las claves foráneas restringen la eliminación de registros vinculados; no hay borrado en cascada del historial.

Migraciones añadidas:
- `2026_10_28_192444_limit_user_name_length`: nombre del personal hasta 150 caracteres.
- `2026_10_28_231043_add_staff_fields_to_users_table`: rol y cuenta activa.
- `2026_10_28_231044_create_veterinary_tables`: diez tablas adicionales, restricciones e índices.

El propietario tiene varias mascotas. Cada mascota tiene un expediente único, citas y atenciones. El expediente agrupa consultas, vacunas y documentos. Cada atención admite una consulta y cada cita una llegada. La consulta enlaza medicamentos, vacunas y documentos del mismo expediente. Los modelos declaran tablas y claves foráneas en español explícitamente.

## Flujo clínico

1. La secretaria busca al propietario antes de registrarlo. Las coincidencias por nombre o teléfono requieren revisión; el documento es único cuando existe. Dos personas pueden compartir nombre.
2. Registra la mascota y el sistema abre su expediente en una transacción. El código HUE se basa en el ID único del paciente; no usa conteos ni genera otro expediente al regresar.
3. En **Atenciones → Registrar llegada**, selecciona paciente y motivo. Deja la cita vacía para visita espontánea, o vincula una cita del mismo paciente.
4. El veterinario comienza la atención desde el tablero. No puede atender a dos pacientes simultáneamente.
5. Abre antecedentes e historial; pulsa **Nueva consulta**. Registra motivo, examen, peso, temperatura, diagnóstico y tratamiento.
6. Guarda la consulta para registrar medicamentos, vacunas y documentos.
7. Finaliza la atención. Si tenía cita, esta pasa a atendida. Una nueva visita crea otra atención y consulta sobre el mismo expediente.

Las urgencias pueden manejarse manualmente: cancelar una llegada todavía en espera, registrar una visita espontánea y comenzar la atención elegida cuando el profesional esté disponible. No hay algoritmo de triaje.

## Medicamentos y vacunaciones

En una consulta activa:
- **Administrado** exige fecha/hora y profesional aplicador.
- **Recetado** guarda fecha/hora y aplicador como NULL; no acredita que el propietario lo haya dado.
- Dosis, unidad, vía, frecuencia, duración e indicaciones son introducidas por el profesional.
- Pueden existir varios registros del mismo medicamento con indicaciones diferentes.
- Registra las vacunas en Vacunaciones una sola vez; no dupliques el mismo evento como medicamento.

No hay generación automática de diagnósticos ni de indicaciones farmacológicas.

## Archivos clínicos y fotografías

Los archivos están en `storage/app/private`. Se admiten PDF/JPG/JPEG/PNG/WebP hasta 10 MB y fotos del paciente hasta 4 MB. Se validan contenido MIME y extensión; SVG y ejecutables no se admiten. Las rutas `documentos.show` comprueban autenticación y rol, permiten ver o descargar y envían cabeceras privadas. Las fotos también se sirven mediante rutas autenticadas.

No hay enlace público al almacenamiento clínico y el disco local no expone su ruta mediante el servidor de archivos de Laravel. Si falla la persistencia, se limpian los documentos recién subidos. La base guarda la ruta generada y el nombre original solo como metadato.

## Rutas y organización

- `/`: portal público.
- `/dashboard`: indicadores reales y consultas recientes según permisos.
- `/propietarios`, `/mascotas`, `/expedientes`: registros y búsqueda.
- `/consultas/{consulta}`: detalle, medicamentos, vacunas y documentos.
- `/medicamentos`: catálogo.
- `/citas`: agenda por fecha, búsqueda y veterinario.
- `/atenciones`: espera, atención y finalizadas.
- `/usuarios`: personal, roles y desactivación.
- `/login` y rutas de recuperación de Fortify.

Controladores: `app/Http/Controllers`. Validación: `app/Http/Requests`. Transacciones y bloqueos: `app/Services`. Modelos: `app/Models`. Interfaz: `resources/views/huellita`, `resources/views/layouts/huellita.blade.php` y `resources/css/app.css`. Configuración pública: `config/huellita.php`.

## Pruebas y comprobaciones

```sh
php artisan test --compact tests/Feature/HuellitaTest.php
php artisan test --compact
php vendor/bin/phpstan analyse
php vendor/bin/pint --format agent
php artisan route:list
php artisan migrate:status
npm run build
```

HuellitaTest cubre permisos, usuarios inactivos, propietarios, pacientes, expediente único, citas conflictivas, llegadas, consultas, medicamentos, vacunas, relaciones cruzadas, documentos privados, escape HTML, segundas visitas y renderizado de formularios. Las pruebas existentes de autenticación y configuración se conservan.

En este equipo el sandbox de Windows hace que PHPUnit, Pint y PHPStan detecten erróneamente archivos como no legibles/no escribibles. Se ejecutaron fuera de ese aislamiento con autorización de la herramienta. `--dirty` de Pint requiere Git y esta carpeta no tiene un repositorio; se aplicó el formateador completo.

Se verificó la concurrencia con dos conexiones reales a MariaDB. Para repetir la certificación en producción: intentar citas solapadas del mismo veterinario al mismo tiempo debe aceptar solo una; dos profesionales deben poder compartir horario. Los bloqueos serializan por fila del profesional dentro de transacciones con reintentos.

La configuración phpunit.mysql.xml fuerza la base huellita_testing. Nunca la cambies para apuntar a huellita: las pruebas de Feature usan RefreshDatabase y restablecen su base de pruebas. Las pruebas de concurrencia crean dos procesos reales, esperan que ambos compitan por el bloqueo y comprueban que solo uno puede completar la operación.

```sh
php vendor/bin/pest --configuration=phpunit.mysql.xml --compact
```

## Guion para un video breve

1. Abre el portal y muestra servicios y contacto.
2. Entra como secretaria: busca/registra un propietario ficticio y una mascota.
3. Muestra el código único del expediente, luego registra llegada sin cita.
4. Entra como veterinario y comienza la atención.
5. Abre antecedentes, guarda una consulta de demostración y distingue medicamento administrado de recetado.
6. Registra una vacuna y adjunta un documento de prueba.
7. Finaliza y muestra la consulta en el historial.
8. Registra otra visita y comprueba que se conservan el paciente y su expediente.

Identifica los datos de prueba como ficticios; no uses información real de clientes en el video.

## Preparación para publicación

Antes de publicar: usa MySQL/InnoDB, credenciales propias y un usuario de base restringido; configura dominio, HTTPS, APP_URL, `APP_ENV=production`, `APP_DEBUG=false`, cookies seguras y un APP_KEY estable. El document root debe apuntar a `public/`. Conserva respaldos de base y almacenamiento privado, configura permisos de escritura en storage/bootstrap/cache y define una política de retención de historiales.

Compila assets y ejecuta las migraciones con respaldo previo. Configura SMTP real para recuperar contraseñas; el transporte log es solo para desarrollo. Revisa que tu servidor bloquee archivos privados y que no sirva el proyecto completo. Comprueba los flujos y la concurrencia en MySQL, y retira las cuentas/datos DEMO antes de usarlo con pacientes reales. No se realizó despliegue en un servicio externo.

## Resultados verificados en esta entrega

- MariaDB: 66 pruebas aprobadas, 239 comprobaciones; incluye dos pruebas con procesos concurrentes reales.
- SQLite: 64 pruebas aprobadas, 227 comprobaciones; dos pruebas de concurrencia MySQL omitidas de forma expresa.
- PHPStan nivel 7 sin errores.
- Migraciones y compilación frontend correctas.
- Revisión visual de portal, acceso y panel; datos DEMO persistidos en MariaDB.
- Pendiente para usar con clientes reales: confirmar contacto/WhatsApp, configurar SMTP y publicar en un servidor con HTTPS.
