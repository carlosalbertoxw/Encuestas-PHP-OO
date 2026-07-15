#!/bin/sh
set -e

# Aplica las migraciones pendientes (y los datos demo en development) antes de
# aceptar tráfico; espera a que MySQL esté disponible con reintentos.
php /var/www/html/bin/migrate.php

exec apache2-foreground
