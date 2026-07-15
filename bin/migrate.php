<?php

declare(strict_types=1);

// Aplica las migraciones pendientes y, en development, inserta los datos de
// demostración. Lo ejecuta el contenedor web antes de arrancar Apache y puede
// lanzarse a mano en local: php bin/migrate.php

require __DIR__ . '/../src/autoload.php';

use Encuestas\Config;
use Encuestas\Data\AnswerRepository;
use Encuestas\Data\Database;
use Encuestas\Data\PollRepository;
use Encuestas\Data\UserRepository;
use Encuestas\Infrastructure\DevDataSeeder;
use Encuestas\Infrastructure\Logger;
use Encuestas\Infrastructure\MigrationRunner;
use Encuestas\Web\Services\PasswordService;

$logger = new Logger();
$db = Database::fromEnvironment();

try {
    (new MigrationRunner($db, $logger))->run();

    if (Config::isDevelopment()) {
        $users = new UserRepository($db);
        $seeder = new DevDataSeeder(
            $users,
            new PollRepository($db),
            new AnswerRepository($db),
            new PasswordService(),
            $logger
        );
        $seeder->seed();
    }
} catch (Throwable $exception) {
    $logger->error('La migración falló: ' . $exception->getMessage());
    exit(1);
}

$logger->info('Migraciones aplicadas correctamente.');
