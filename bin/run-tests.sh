#!/bin/sh
# Ejecuta la suite completa de pruebas (unitarias, integración y HTTP de extremo
# a extremo) contra un MySQL 8.4 efímero en Docker. No requiere PHP, Composer ni
# MySQL instalados en la máquina; al terminar no deja nada corriendo.
#
# Uso: sh bin/run-tests.sh
set -eu
cd "$(dirname "$0")/.."

# Evita que Git Bash (Windows) reescriba las rutas /app de los contenedores.
export MSYS_NO_PATHCONV=1

SUFFIX=$$
NETWORK="encuestas-tests-net-$SUFFIX"
DB_CONTAINER="encuestas-tests-db-$SUFFIX"
IMAGE="encuestas-php-tests"

cleanup() {
    docker rm -f "$DB_CONTAINER" >/dev/null 2>&1 || true
    docker network rm "$NETWORK" >/dev/null 2>&1 || true
}
trap cleanup EXIT INT TERM

echo ">> Construyendo la imagen de la aplicación (incluye PHP y pdo_mysql)..."
docker build -q -t "$IMAGE" . >/dev/null

if [ ! -d vendor ]; then
    echo ">> Instalando dependencias de desarrollo con Composer..."
    docker run --rm -v "$PWD:/app" -w /app composer:2 \
        install --no-progress --prefer-dist --ignore-platform-req=ext-pdo_mysql
fi

echo ">> Levantando un MySQL 8.4 efímero..."
docker network create "$NETWORK" >/dev/null
docker run -d --rm --name "$DB_CONTAINER" --network "$NETWORK" \
    -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=encuestas_test \
    mysql:8.4 >/dev/null

echo ">> Esperando a que MySQL acepte conexiones..."
attempt=0
until docker exec "$DB_CONTAINER" mysqladmin ping -h 127.0.0.1 -u root -proot >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        echo "MySQL no arrancó a tiempo" >&2
        exit 1
    fi
    sleep 2
done

echo ">> Ejecutando la suite de pruebas..."
docker run --rm --network "$NETWORK" --entrypoint php \
    -v "$PWD:/app" -w /app \
    -e TEST_DB_HOST="$DB_CONTAINER" \
    -e TEST_DB_PORT=3306 \
    -e TEST_DB_DATABASE=encuestas_test \
    -e TEST_DB_USERNAME=root \
    -e TEST_DB_PASSWORD=root \
    "$IMAGE" vendor/bin/phpunit --do-not-cache-result

echo ">> Listo: suite completa en verde."
