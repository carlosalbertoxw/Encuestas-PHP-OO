# Decisiones de diseño

Registro breve de las decisiones que dan forma al proyecto. Si una decisión cambia, se agrega una entrada nueva.

## 1. PHP orientado a objetos sin framework

- **Decisión:** router, vistas y composición manual propios (`Encuestas\Web\Application`); Composer solo para el autoloader y las herramientas de desarrollo.
- **Descartado:** Laravel o Symfony: el objetivo del ejercicio es entender cada pieza.

## 2. Sesiones y contadores de intentos en MySQL

- **Contexto:** cada petición PHP es un proceso nuevo y la app debe poder correr en varias réplicas.
- **Decisión:** `DbSessionHandler` y `a_attempt_counters` con incremento atómico.
- **Descartado:** sesiones en archivos y Redis (un servicio más que operar).

## 3. Bandeja de salida y proceso despachador

- **Contexto:** enviar por SMTP dentro de la petición rompía el registro si el servidor de correo fallaba.
- **Decisión:** las peticiones encolan en `a_outbox` y `bin/outbox-worker.php` (servicio `mailer`) envía con reintentos y espera exponencial, con `FOR UPDATE SKIP LOCKED`.
- **Descartado:** `try/catch` síncrono (el correo se pierde) y `fastcgi_finish_request` (no existe con mod_php).

## 4. Cliente SMTP propio

- **Decisión:** cliente mínimo con STARTTLS, TLS implícito o sin cifrado, TLS 1.2/1.3 con verificación del certificado, para mantener cero dependencias de runtime.
- **Descartado:** `symfony/mailer` (menos código propio, pero la primera dependencia de runtime).

## 5. Proxies de confianza explícitos

- **Decisión:** `X-Forwarded-For`/`X-Forwarded-Proto` solo se aceptan de `TRUSTED_PROXIES` (vacío por defecto).
