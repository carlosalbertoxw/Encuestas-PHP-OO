# Encuestas

Aplicación web de encuestas escrita en PHP 8 orientado a objetos (sin framework) y MySQL. Cada usuario registrado puede crear encuestas, ordenarlas en su tablero, compartir su perfil público y recibir respuestas de otros usuarios (calificación de 1 a 5 estrellas más comentario).

## Características

- Registro con **verificación de correo** e inicio de sesión con sesiones de PHP **almacenadas en MySQL** (sobreviven a reinicios del contenedor y permiten varias réplicas), con cookie `HttpOnly`, `SameSite=Lax`, regeneración de id al iniciar sesión y **expiración deslizante** de 8 horas.
- **Restablecimiento de contraseña** por enlace con token firmado (HMAC-SHA256) y caducidad.
- Contraseñas con hash de `password_hash` (bcrypt/argon2 según `PASSWORD_DEFAULT`), con regeneración transparente cuando el hash usa parámetros antiguos.
- Sello de seguridad (*security stamp*): al cambiar la contraseña se invalidan las demás sesiones abiertas.
- Gestión de cuenta: editar nombre, cambiar usuario, correo y contraseña, y eliminar la cuenta (con borrado en cascada de sus datos).
- CRUD de encuestas con posición configurable en el tablero.
- Perfil público por nombre de usuario (`/User/Index?profile=usuario`) con las encuestas de esa persona.
- Respuestas a encuestas: calificación de 1 a 5 estrellas y comentario, **una por usuario y encuesta**; el dueño ve todas las respuestas recibidas, paginadas.
- Protección CSRF en todos los POST y validación en servidor (`Validator`) y en cliente (atributos HTML5 + JS sin jQuery).
- Rate limiting por IP en login/registro/recuperación más **bloqueo por cuenta** tras varios intentos fallidos (estado compartido en MySQL); cabeceras de seguridad (incluida CSP sin `unsafe-inline` en scripts) y logging de eventos.
- Soporte de **proxies inversos de confianza** (`TRUSTED_PROXIES`): las cabeceras `X-Forwarded-For`/`X-Forwarded-Proto` solo se aceptan desde ellos, y redirección global a HTTPS activable con `FORCE_HTTPS`.
- **Análisis estático** con PHPStan (nivel 8) y estilo PSR-12 con PHP_CodeSniffer, aplicados en cada push por el CI.
- Migraciones de esquema versionadas: se aplican automáticamente al arrancar el contenedor y quedan registradas en la tabla `schemaversions`.
- UI con Bootstrap 5.3 (sin jQuery) y assets con *cache busting* (`?v=` por fecha de modificación).
- Endpoint de salud `/health`, app contenerizada (Apache como usuario no root) y CI en GitHub Actions.
- Sin dependencias de runtime: autoloader PSR-4 propio; Composer solo para las herramientas de desarrollo (PHPUnit).

## Requisitos

- [Docker](https://www.docker.com/) con Docker Compose (no hace falta PHP instalado en el host).
- Opcional, para desarrollo fuera de Docker: PHP >= 8.2 con `pdo_mysql`, y [Composer](https://getcomposer.org/) para las pruebas.

## Instalación y ejecución

### Opción A — Todo en Docker

Construye y levanta la aplicación (puerto 8081) junto con MySQL 8.4 (puerto 3307):

```bash
docker compose up --build
```

La app arranca en `http://localhost:8081` en modo Producción (sin datos de demostración). Regístrate para crear una cuenta; como el envío de correo está simulado, el enlace de confirmación aparece en los logs: `docker compose logs web`.

> Los puertos por defecto (web 8081, MySQL 3307) evitan choques con otros servicios que suelen ocupar el 8080 y el 3306; se personalizan con las variables `WEB_PORT` y `MYSQL_PORT` del `.env`.

Para el modo desarrollo (datos demo + errores detallados), crea un `.env` a partir de `.env.example` con `APP_ENV=development` y vuelve a levantar.

### Opción B — Base de datos en Docker + app local (desarrollo)

```bash
# 1. Levantar solo la base de datos
docker compose up -d db

# 2. Copiar la configuración local (APP_ENV=development por defecto)
cp .env.example .env

# 3. Aplicar migraciones e insertar los datos demo
php bin/migrate.php

# 4. Ejecutar la aplicación
php -S localhost:8081 -t public public/index.php
```

### Usuarios de demostración

En el entorno **development**, si la base está vacía se insertan dos cuentas de prueba **ya confirmadas**:

| Correo               | Contraseña | Nota                                            |
|----------------------|------------|--------------------------------------------------|
| `demo@encuestas.dev` | `demo1234` | Tiene una encuesta de ejemplo con una respuesta |
| `ana@encuestas.dev`  | `ana12345` | Responde la encuesta de la cuenta demo         |

Al registrar una cuenta nueva se requiere confirmar el correo. Con el envío simulado, el enlace de confirmación (y el de restablecimiento) se escriben en el log de la aplicación.

## Configuración

Toda la configuración se lee de variables de entorno (con un archivo `.env` opcional para desarrollo local, que no se versiona). Personalízala copiando `.env.example` a `.env`:

| Variable              | Descripción                                      | Por defecto |
|-----------------------|--------------------------------------------------|-------------|
| `MYSQL_ROOT_PASSWORD` | Contraseña de root de MySQL                      | `root`      |
| `MYSQL_DATABASE`      | Nombre de la base de datos                       | `encuestas` |
| `MYSQL_USER`          | Usuario de la aplicación                         | `encuestas` |
| `MYSQL_PASSWORD`      | Contraseña del usuario                           | `encuestas` |
| `MYSQL_PORT`          | Puerto de MySQL expuesto en el host              | `3307`      |
| `WEB_PORT`            | Puerto de la app expuesto en el host             | `8081`      |
| `APP_ENV`             | `development` o `production`                     | `production` (en Docker) |
| `APP_KEY`             | Clave para firmar los tokens de correo           | autogenerada en desarrollo |
| `TRUSTED_PROXIES`     | IPs/CIDR de proxies inversos de confianza        | (vacío: no se acepta `X-Forwarded-*`) |
| `FORCE_HTTPS`         | `true` redirige todo el http a https (producción) | `false`    |
| `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` | Conexión de la app (solo fuera de Docker) | valores del compose |

Los valores por defecto son solo para desarrollo local. En producción define `APP_KEY` con una clave aleatoria (`openssl rand -hex 32`); los tokens de confirmación y restablecimiento se firman con ella.

El endpoint `GET /health` comprueba la conectividad con la base de datos (útil como sonda de *readiness/liveness*).

## Estructura del proyecto

```
public/               # Único directorio servible: front controller, assets (Bootstrap 5.3, sin jQuery)
src/
  Model/              # Entidades: User, UserProfile, Poll, Answer
  Data/               # Repositorios PDO (RepositoryResult, PagedResult) y fábrica de conexiones
  Web/
    Controllers/      # User, Poll, Answer, Error
    Services/         # AuthService, PasswordService, TokenService, EmailSender, AccountLockout, RateLimiter
    Router.php        # Rutas {Controller}/{Action}/{id?} con requisito de sesión por ruta
    Csrf.php          # Token CSRF para todos los POST
    Validator.php     # Validación de formularios en el servidor
    RequestContext.php   # IP y esquema reales respetando TRUSTED_PROXIES
    DbSessionHandler.php # Sesiones almacenadas en MySQL
  Views/              # Plantillas PHP (layout + vistas por controlador)
  Infrastructure/     # MigrationRunner, DevDataSeeder, Logger
migrations/           # Scripts SQL versionados (tabla schemaversions)
bin/migrate.php       # Aplica migraciones (+ datos demo en development)
bin/run-tests.sh      # Suite completa contra un MySQL efímero en Docker
tests/                # PHPUnit: unitarias, integración y HTTP E2E contra MySQL
Dockerfile            # PHP 8.3 + Apache, usuario no root, puerto 8080 interno
docker-compose.yml    # Servicios web + MySQL 8.4 con volumen persistente y healthcheck
```

## Pruebas

La forma más sencilla (solo requiere Docker): levanta un MySQL 8.4 efímero, ejecuta toda la suite en contenedores y limpia al terminar.

```bash
sh bin/run-tests.sh
```

Hay pruebas unitarias (hashing, tokens, bloqueo de cuenta, rate limiting, validación, paginación, proxies de confianza), de integración de repositorios contra un MySQL real, y **de extremo a extremo del pipeline HTTP**: arrancan la aplicación completa con el servidor embebido de PHP y comprueban salud, autorización, login con cookie y rechazo de CSRF. Las que necesitan base de datos se omiten si no se define `TEST_DB_HOST`.

En CI (GitHub Actions) cada push y pull request ejecuta lint de sintaxis, **PHPStan (nivel 8)**, **PHP_CodeSniffer (PSR-12)**, la suite completa contra un servicio `mysql:8.4` y la construcción de la imagen Docker.

Con PHP instalado también puedes correrlas directamente:

```bash
composer install
vendor/bin/phpunit          # unitarias (las de BD se omiten sin TEST_DB_HOST)
composer analyse            # PHPStan
composer cs                 # PHP_CodeSniffer
TEST_DB_HOST=127.0.0.1 TEST_DB_PORT=3307 TEST_DB_DATABASE=encuestas_test \
TEST_DB_USERNAME=root TEST_DB_PASSWORD=root vendor/bin/phpunit   # con BD dedicada
```

## Base de datos

El esquema vive en [migrations/](migrations) como scripts versionados que el `MigrationRunner` aplica al arrancar (MySQL 8.4, `utf8mb4`, InnoDB, con columnas de auditoría `created_at`/`updated_at`, sello de seguridad, confirmación de correo e índice compuesto para el tablero). Para evolucionar el esquema, agrega un script nuevo (`0002_...sql`) — nunca edites uno ya aplicado:

| Tabla                | Contenido                                                            | Relaciones                                               |
|----------------------|----------------------------------------------------------------------|-----------------------------------------------------------|
| `a_users`            | Cuentas: correo, confirmación, hash y sello                          | —                                                         |
| `a_users_profiles`   | Perfil público: usuario y nombre                                     | 1–1 con `a_users` (cascada)                               |
| `a_polls`            | Encuestas: título, descripción, posición                             | N–1 con `a_users_profiles` (cascada)                      |
| `a_answers`          | Respuestas: estrellas (1–5) y comentario, únicas por usuario/encuesta | N–1 con `a_polls` y `a_users_profiles` (cascada)          |
| `a_sessions`         | Sesiones de la aplicación                                            | —                                                         |
| `a_attempt_counters` | Contadores de rate limiting y bloqueo de cuenta                      | —                                                         |

Para regenerar la base de datos desde cero (las migraciones y los datos demo se reaplican al arrancar la app):

```bash
docker compose down -v && docker compose up -d
```
