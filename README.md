# Encuestas

Aplicación web de encuestas escrita en PHP 8.5 orientado a objetos (sin framework) y MySQL. Cada usuario registrado puede crear encuestas, ordenarlas en su tablero, compartir su perfil público y recibir respuestas de otros usuarios (calificación de 1 a 5 estrellas más comentario).

## Características

- Registro con **verificación de correo** (con página para pedir un enlace nuevo si se perdió o caducó) e inicio de sesión con sesiones de PHP **almacenadas en MySQL** (sobreviven a reinicios del contenedor y permiten varias réplicas), con cookie `HttpOnly`, `SameSite=Lax`, regeneración de id al iniciar sesión y **expiración deslizante** de 8 horas.
- **Restablecimiento de contraseña** por enlace con token firmado (HMAC-SHA256) y caducidad.
- **Cambio de correo con confirmación**: el correo nuevo queda pendiente y solo se aplica al abrir el enlace enviado a esa dirección; se avisa al correo anterior y se cierran las sesiones.
- Enlaces de los correos construidos con la URL pública configurada (`APP_BASE_URL`), nunca con la cabecera `Host` de la petición.
- Correos con **bandeja de salida** (tabla `a_outbox`): las peticiones solo encolan y el proceso `bin/outbox-worker.php` (servicio `mailer` en Docker) los envía por **SMTP** con reintentos y espera exponencial, así que una caída del servidor de correo no rompe el registro ni la recuperación. El cliente SMTP es propio y sin dependencias: STARTTLS, TLS implícito (puerto 465) o sin cifrado, con TLS 1.2/1.3 y verificación del certificado. En local, **Mailpit** captura los correos y los muestra en una interfaz web.
- Contraseñas con hash de `password_hash` (bcrypt/argon2 según `PASSWORD_DEFAULT`), con regeneración transparente cuando el hash usa parámetros antiguos. El login hace el mismo trabajo exista o no la cuenta, para no revelar correos por el tiempo de respuesta.
- Sello de seguridad (*security stamp*): al cambiar la contraseña o el correo, las demás sesiones se cierran en cuanto revisan el sello (cada 30 segundos como máximo).
- Gestión de cuenta: editar nombre, cambiar usuario, correo y contraseña, y eliminar la cuenta (con borrado en cascada de sus datos).
- CRUD de encuestas con posición configurable; tablero, perfil público y respuestas **paginados**.
- Perfil público por nombre de usuario (`/User/Index?profile=usuario`).
- Respuestas: calificación de 1 a 5 estrellas y comentario, **una por usuario y encuesta**.
- Protección CSRF en todos los POST y validación en servidor (`Validator`) y en cliente (atributos HTML5 + JS sin jQuery).
- Límite de peticiones por IP en los formularios de acceso y por usuario en el cambio de correo, más **bloqueo por cuenta** tras varios intentos fallidos (contadores compartidos en MySQL); cabeceras de seguridad (incluida CSP sin `unsafe-inline` en scripts) y logs sin correos en claro.
- Logs en **JSON** (una línea por evento, con contexto) y un identificador por petición en la cabecera `X-Request-Id`.
- Soporte de **proxies inversos de confianza** (`TRUSTED_PROXIES`): las cabeceras `X-Forwarded-For`/`X-Forwarded-Proto` solo se aceptan desde ellos, y redirección global a HTTPS activable con `FORCE_HTTPS`.
- **Análisis estático** con PHPStan (nivel 8), estilo PSR-12 con PHP_CodeSniffer y Semgrep, aplicados en cada push por el CI.
- Migraciones de esquema versionadas: se aplican automáticamente al arrancar el contenedor y quedan registradas en la tabla `schemaversions`.
- UI con Bootstrap 5.3 (sin jQuery) y assets con *cache busting* (`?v=` por fecha de modificación).
- Sondas `/health/live` (proceso) y `/health/ready` (incluye la base de datos), con `HEALTHCHECK` en la imagen.
- Sin dependencias de runtime: Composer genera el autoloader (PSR-4 optimizado) y aporta las herramientas de desarrollo.

## Requisitos

- [Docker](https://www.docker.com/) con Docker Compose (no hace falta PHP instalado en el host).
- Opcional, para desarrollo fuera de Docker: PHP 8.5 con `pdo_mysql` y [Composer](https://getcomposer.org/).

## Instalación y ejecución

### Opción A — Todo en Docker

Crea el `.env` con una clave para firmar los tokens (obligatoria) y levanta la aplicación (puerto 8081), el despachador de correos, MySQL 8.4 (puerto 3307) y Mailpit (interfaz en el puerto 8026):

```bash
cp .env.example .env
```

Edita `.env` y asigna `APP_KEY` (por ejemplo, con el resultado de `openssl rand -hex 32`). Después:

```bash
docker compose up --build
```

Con `APP_ENV=development` (el valor de `.env.example`) se insertan los datos de demostración y los errores se muestran en pantalla; con `APP_ENV=production`, no. Los correos (confirmación, restablecimiento, cambio de correo) se leen en Mailpit: `http://localhost:8026`.

> Los puertos por defecto (web 8081, MySQL 3307, Mailpit 1026/8026) evitan choques con otros servicios; se personalizan con `WEB_PORT`, `MYSQL_PORT`, `MAILPIT_SMTP_PORT` y `MAILPIT_UI_PORT` en el `.env` (si cambias `WEB_PORT`, cambia también `APP_BASE_URL`).

### Opción B — Base de datos y Mailpit en Docker + app local (desarrollo)

```bash
# 1. Copiar la configuración local (APP_ENV=development por defecto) y asignar APP_KEY
cp .env.example .env

# 2. Levantar MySQL y Mailpit
docker compose up -d db mailpit

# 3. Instalar dependencias (genera el autoloader)
composer install

# 4. Aplicar migraciones e insertar los datos demo
php bin/migrate.php

# 5. Ejecutar la aplicación
php -S localhost:8081 -t public public/index.php

# 6. En otra terminal, el despachador de correos (envía a Mailpit con los valores de .env.example)
php bin/outbox-worker.php
```

Las peticiones encolan los correos en la tabla `a_outbox` y los envía `bin/outbox-worker.php` (en Docker, el servicio `mailer`); sin el despachador en marcha los correos esperan en la tabla. Nunca se escriben en el log, porque llevan enlaces con tokens.

### Usuarios de demostración

En el entorno **development**, si la base está vacía se insertan dos cuentas de prueba **ya confirmadas**:

| Correo               | Contraseña | Nota                                            |
|----------------------|------------|-------------------------------------------------|
| `demo@encuestas.dev` | `demo1234` | Tiene una encuesta de ejemplo con una respuesta |
| `ana@encuestas.dev`  | `ana12345` | Responde la encuesta de la cuenta demo          |

## Configuración

Toda la configuración se lee de variables de entorno (con un archivo `.env` opcional para desarrollo local, que no se versiona). Personalízala copiando `.env.example` a `.env`:

| Variable              | Descripción                                       | Por defecto |
|-----------------------|---------------------------------------------------|-------------|
| `MYSQL_ROOT_PASSWORD` | Contraseña de root de MySQL                       | `root`      |
| `MYSQL_DATABASE`      | Nombre de la base de datos                        | `encuestas` |
| `MYSQL_USER`          | Usuario de la aplicación                          | `encuestas` |
| `MYSQL_PASSWORD`      | Contraseña del usuario                            | `encuestas` |
| `MYSQL_PORT`          | Puerto de MySQL en el host (solo `127.0.0.1`)     | `3307`      |
| `WEB_PORT`            | Puerto de la app expuesto en el host              | `8081`      |
| `APP_ENV`             | `development` o `production`                      | `production` (en Docker) |
| `APP_KEY`             | Clave para firmar los tokens de correo. **Obligatoria** con Docker Compose y en producción | autogenerada solo con `php -S` en development |
| `APP_BASE_URL`        | URL pública; base de los enlaces de los correos. **Obligatoria** fuera de development | `http://localhost:8081` |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION` | Servidor SMTP del despachador (`starttls`, `tls` para el puerto 465 o `none`). **Obligatorio** | Mailpit en Docker |
| `RATE_LIMIT_AUTH`, `RATE_LIMIT_EMAIL_CHANGE` | Peticiones permitidas por IP y minuto en los formularios de acceso, y por usuario y hora en el cambio de correo | `10`, `3` |
| `SMTP_USER`, `SMTP_PASSWORD`, `SMTP_FROM` | Credenciales (AUTH LOGIN) y remitente  | *(vacío)*, *(vacío)*, `Encuestas <no-reply@encuestas.local>` |
| `MAILPIT_SMTP_PORT`, `MAILPIT_UI_PORT` | Puertos de Mailpit en el host (solo `127.0.0.1`) | `1026`, `8026` |
| `TRUSTED_PROXIES`     | IPs/CIDR de proxies inversos de confianza         | (vacío: no se acepta `X-Forwarded-*`) |
| `FORCE_HTTPS`         | `true` redirige todo el http a https (producción) | `false`     |
| `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` | Conexión de la app (solo fuera de Docker) | valores del compose |

Los valores por defecto son solo para desarrollo local.

## Estructura del proyecto

```
public/               # Único directorio servible: front controller, assets (Bootstrap 5.3, sin jQuery)
src/
  Model/              # Entidades: User, UserProfile, Poll, Answer
  Data/               # Repositorios PDO (RepositoryResult, PagedResult), EmailOutbox y fábrica de conexiones
  Web/
    Controllers/      # User (inicio y acceso), Account (gestión de la cuenta), Poll, Answer, Error
    Services/         # AccountService (flujos de la cuenta), AuthService, PasswordService, TokenService,
                      # SmtpEmailSender, AccountLockout, RateLimiter, DbAttemptCounterStore
    Application.php   # Atiende la petición: cabeceras, sesión, CSRF, servicios y tabla de rutas
    Router.php        # Rutas {Controller}/{Action}/{id?} con requisito de sesión por ruta
    Csrf.php          # Token CSRF para todos los POST
    Validator.php     # Validación de formularios en el servidor
    RequestContext.php   # IP y esquema reales respetando TRUSTED_PROXIES
    DbSessionHandler.php # Sesiones almacenadas en MySQL
  Views/              # Plantillas PHP (layout + vistas por controlador)
  Infrastructure/     # MigrationRunner, DevDataSeeder, Logger (JSON)
migrations/           # Scripts SQL versionados (tabla schemaversions)
bin/migrate.php       # Aplica migraciones (+ datos demo en development)
bin/outbox-worker.php # Envía los correos de la bandeja de salida (servicio mailer)
bin/run-tests.sh      # Suite completa contra un MySQL efímero en Docker
tests/                # PHPUnit 13: unitarias, integración y HTTP E2E contra MySQL
docs/decisiones.md    # Decisiones de diseño y alternativas descartadas
Dockerfile            # Multi-stage: autoloader de Composer + PHP 8.5 y Apache, usuario no root
docker-compose.yml    # web + mailer + MySQL 8.4 + Mailpit, con volumen persistente y healthcheck
```

## Pruebas

La forma más sencilla (solo requiere Docker): levanta un MySQL 8.4 efímero, ejecuta toda la suite en contenedores y limpia al terminar.

```bash
sh bin/run-tests.sh
```

Hay pruebas unitarias (hashing, tokens de confirmación, cambio de correo y restablecimiento, bloqueo de cuenta, límite de peticiones, validación, paginación, proxies de confianza, URL pública, configuración SMTP, logs JSON y enmascarado de correos), de integración de repositorios y de la bandeja de salida contra un MySQL real, y **de extremo a extremo del pipeline HTTP**: arrancan la aplicación completa con el servidor embebido de PHP y comprueban las sondas de salud, autorización, login con cookie, rechazo de CSRF y los flujos completos (registro → confirmación → login, reenvío de la confirmación, restablecimiento de contraseña y cambio de correo, leyendo los enlaces de los correos encolados en `a_outbox`). Las que necesitan base de datos se omiten si no se define `TEST_DB_HOST`.

Con PHP instalado también puedes correrlas directamente:

```bash
composer install
vendor/bin/phpunit          # unitarias (las de BD se omiten sin TEST_DB_HOST)
composer analyse            # PHPStan
composer cs                 # PHP_CodeSniffer
composer audit              # Vulnerabilidades conocidas en las dependencias
TEST_DB_HOST=127.0.0.1 TEST_DB_PORT=3307 TEST_DB_DATABASE=encuestas_test \
TEST_DB_USERNAME=root TEST_DB_PASSWORD=root vendor/bin/phpunit   # con BD dedicada
```

## Integración continua

En cada push a `master` y en cada pull request, GitHub Actions ([ci.yml](.github/workflows/ci.yml)) ejecuta:

| Job              | Qué hace                                                                                                   |
|------------------|------------------------------------------------------------------------------------------------------------|
| `build-and-test` | Lint de sintaxis, `composer validate`, `composer audit`, PHPStan (nivel 8), PHPCS (PSR-12) y la suite completa contra `mysql:8.4` |
| `imagen`         | Construye la imagen Docker y la escanea con Trivy (falla con vulnerabilidades altas o críticas corregibles) |
| `semgrep`        | Análisis estático de seguridad (SAST) con las reglas de PHP y de auditoría de seguridad de Semgrep          |

Las acciones de GitHub se fijan por SHA (con la versión en un comentario) y la imagen de Semgrep por versión. Dependabot ([dependabot.yml](.github/dependabot.yml)) propone cada semana actualizaciones de Composer, de la imagen del Dockerfile y de Docker Compose, y de GitHub Actions. Los PR de versiones menores y de parche se fusionan solos cuando el CI pasa ([dependabot-auto-merge.yml](.github/workflows/dependabot-auto-merge.yml)); los de versión mayor se revisan a mano.

## Base de datos

El esquema vive en [migrations/](migrations) como scripts versionados que el `MigrationRunner` aplica al arrancar (MySQL 8.4, `utf8mb4`, InnoDB). Para evolucionar el esquema, agrega un script nuevo (`0004_...sql`); nunca edites uno ya aplicado:

| Tabla                | Contenido                                                             | Relaciones                                       |
|----------------------|-----------------------------------------------------------------------|--------------------------------------------------|
| `a_users`            | Cuentas: correo, correo pendiente, confirmación, hash y sello         | —                                                |
| `a_users_profiles`   | Perfil público: usuario y nombre                                      | 1–1 con `a_users` (cascada)                      |
| `a_polls`            | Encuestas: título, descripción, posición                              | N–1 con `a_users_profiles` (cascada)             |
| `a_answers`          | Respuestas: estrellas (1–5) y comentario, únicas por usuario/encuesta | N–1 con `a_polls` y `a_users_profiles` (cascada) |
| `a_sessions`         | Sesiones de la aplicación                                             | —                                                |
| `a_attempt_counters` | Contadores del límite de peticiones y del bloqueo de cuenta           | —                                                |
| `a_outbox`           | Correos pendientes de envío, con intentos y próximo reintento         | —                                                |

El despachador borra cada hora los correos que agotaron sus reintentos hace más de una semana; los contadores caducados se purgan al registrar intentos nuevos.

Para regenerar la base de datos desde cero (las migraciones se reaplican al arrancar el contenedor, y los datos demo si `APP_ENV=development`):

```bash
docker compose down -v && docker compose up -d
```

## Decisiones de diseño

Las decisiones principales (y las alternativas descartadas) están en [docs/decisiones.md](docs/decisiones.md).

## Licencia

[MIT](LICENSE).
