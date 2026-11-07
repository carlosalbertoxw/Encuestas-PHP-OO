<?php

declare(strict_types=1);

namespace Encuestas\Tests;

use Encuestas\Data\AnswerRepository;
use Encuestas\Data\Database;
use Encuestas\Data\PollRepository;
use Encuestas\Data\RepositoryResult;
use Encuestas\Data\UserRepository;
use Encuestas\Infrastructure\Logger;
use Encuestas\Infrastructure\MigrationRunner;
use Encuestas\Model\Answer;
use Encuestas\Model\Poll;
use Encuestas\Model\UserProfile;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de integración contra un MySQL real. Se omiten si no se define
 * TEST_DB_HOST (en CI las provee el servicio mysql:8.4 del workflow; en local
 * las levanta bin/run-tests.sh con una base efímera).
 */
final class RepositoryIntegrationTest extends TestCase
{
    private static ?Database $database = null;

    public static function setUpBeforeClass(): void
    {
        $host = getenv('TEST_DB_HOST');
        if ($host === false || $host === '') {
            return;
        }
        self::$database = new Database(
            $host,
            (int) (getenv('TEST_DB_PORT') ?: '3306'),
            getenv('TEST_DB_DATABASE') ?: 'encuestas_test',
            getenv('TEST_DB_USERNAME') ?: 'root',
            getenv('TEST_DB_PASSWORD') ?: 'root',
        );
        (new MigrationRunner(self::$database, new Logger('', 'php://memory')))->run();
    }

    protected function setUp(): void
    {
        if (self::$database === null) {
            $this->markTestSkipped('Define TEST_DB_HOST para ejecutar las pruebas de integración.');
        }
        // Limpia los datos entre pruebas (el esquema borra en cascada).
        $this->db()->connection()->exec('DELETE FROM a_users');
        $this->db()->connection()->exec('DELETE FROM a_attempt_counters');
    }

    public function test_crear_usuario_genera_cuenta_y_perfil(): void
    {
        $users = new UserRepository($this->db());

        $this->assertSame(RepositoryResult::Success, $users->createUser('demo@encuestas.dev', 'hash', 'sello-1'));

        $profile = $this->profile('demo@encuestas.dev');
        $this->assertSame('usuario' . $profile->user->id, $profile->userName);
        $this->assertFalse($profile->user->emailConfirmed);
    }

    public function test_el_correo_duplicado_se_reporta_como_duplicate(): void
    {
        $users = new UserRepository($this->db());
        $users->createUser('demo@encuestas.dev', 'hash', 'sello-1');

        $this->assertSame(RepositoryResult::Duplicate, $users->createUser('demo@encuestas.dev', 'hash', 'sello-2'));
    }

    public function test_cambiar_contrasena_rota_el_sello_de_seguridad(): void
    {
        $users = new UserRepository($this->db());
        $users->createUser('demo@encuestas.dev', 'hash', 'sello-1');
        $profile = $this->profile('demo@encuestas.dev');

        $users->changePassword($profile->user->id, 'hash-nuevo', 'sello-2');

        $this->assertSame('sello-2', $users->getSecurityStamp($profile->user->id));
    }

    public function test_crud_de_encuestas_valida_al_propietario(): void
    {
        $users = new UserRepository($this->db());
        $polls = new PollRepository($this->db());
        $users->createUser('demo@encuestas.dev', 'hash', 'sello-1');
        $users->createUser('ana@encuestas.dev', 'hash', 'sello-2');
        $demo = $this->profile('demo@encuestas.dev');
        $ana = $this->profile('ana@encuestas.dev');

        $this->assertTrue($polls->addPoll(self::newPoll($demo->user->id)));

        $saved = $polls->getPolls($demo->user->id, 1)->items[0];
        $this->assertNull($polls->getPoll($ana->user->id, $saved->id), 'Otro usuario no debe poder editarla');
        $this->assertFalse($polls->deletePoll($ana->user->id, $saved->id), 'Otro usuario no debe poder borrarla');
        $this->assertTrue($polls->deletePoll($demo->user->id, $saved->id));
    }

    public function test_solo_una_respuesta_por_usuario_y_encuesta(): void
    {
        $users = new UserRepository($this->db());
        $polls = new PollRepository($this->db());
        $answers = new AnswerRepository($this->db());
        $users->createUser('demo@encuestas.dev', 'hash', 'sello-1');
        $demo = $this->profile('demo@encuestas.dev');

        $polls->addPoll(self::newPoll($demo->user->id));
        $pollId = $polls->getPolls($demo->user->id, 1)->items[0]->id;

        $answer = new Answer();
        $answer->stars = 5;
        $answer->comment = 'Comentario';
        $answer->pollId = $pollId;
        $answer->userId = $demo->user->id;

        $this->assertSame(RepositoryResult::Success, $answers->addAnswer($answer));
        $this->assertSame(RepositoryResult::Duplicate, $answers->addAnswer($answer));

        $page = $answers->getAnswersForPoll($pollId, 1, 10);
        $this->assertSame(1, $page->totalCount);
        $this->assertSame('usuario' . $demo->user->id, $page->items[0]->userName);
    }

    public function test_eliminar_la_cuenta_borra_en_cascada(): void
    {
        $users = new UserRepository($this->db());
        $polls = new PollRepository($this->db());
        $users->createUser('demo@encuestas.dev', 'hash', 'sello-1');
        $demo = $this->profile('demo@encuestas.dev');

        $polls->addPoll(self::newPoll($demo->user->id));

        $this->assertSame(RepositoryResult::Success, $users->deleteAccount($demo->user->id));
        $this->assertNull($users->getProfileByEmail('demo@encuestas.dev'));
        $this->assertSame([], $polls->getPolls($demo->user->id, 1)->items);
    }

    public function test_las_encuestas_se_paginan_por_posicion(): void
    {
        $users = new UserRepository($this->db());
        $polls = new PollRepository($this->db());
        $users->createUser('demo@encuestas.dev', 'hash', 'sello-1');
        $demo = $this->profile('demo@encuestas.dev');
        for ($position = 12; $position >= 1; $position--) {
            $poll = self::newPoll($demo->user->id);
            $poll->position = $position;
            $polls->addPoll($poll);
        }

        $second = $polls->getPolls($demo->user->id, 2, 10);

        $this->assertSame(12, $second->totalCount);
        $this->assertSame([11, 12], array_map(static fn (Poll $p): int => $p->position, $second->items));
    }

    public function test_el_correo_pendiente_solo_se_aplica_al_confirmarlo(): void
    {
        $users = new UserRepository($this->db());
        $users->createUser('viejo@test.com', 'hash', 'sello-1');
        $userId = $this->profile('viejo@test.com')->user->id;

        $this->assertSame(RepositoryResult::Success, $users->setPendingEmail($userId, 'nuevo@test.com'));
        $this->assertSame('viejo@test.com', $users->getUser($userId)?->email);
        $this->assertSame('nuevo@test.com', $users->getUser($userId)?->pendingEmail);

        // Un enlace para otra dirección (p. ej. de una solicitud anterior) no aplica nada.
        $this->assertSame(RepositoryResult::NotFound, $users->confirmEmailChange($userId, 'otro@test.com', 'sello-2'));
        $this->assertSame(RepositoryResult::Success, $users->confirmEmailChange($userId, 'nuevo@test.com', 'sello-2'));

        $changed = $users->getUser($userId);
        $this->assertNotNull($changed);
        $this->assertSame('nuevo@test.com', $changed->email);
        $this->assertNull($changed->pendingEmail);
        $this->assertTrue($changed->emailConfirmed);
        $this->assertSame('sello-2', $users->getSecurityStamp($userId));
    }

    public function test_confirmar_un_correo_que_ya_usa_otra_cuenta_devuelve_duplicate(): void
    {
        $users = new UserRepository($this->db());
        $users->createUser('ocupado@test.com', 'hash', 's');
        $users->createUser('quiere-cambiar@test.com', 'hash', 's');
        $userId = $this->profile('quiere-cambiar@test.com')->user->id;
        $users->setPendingEmail($userId, 'ocupado@test.com');

        $this->assertSame(RepositoryResult::Duplicate, $users->confirmEmailChange($userId, 'ocupado@test.com', 's2'));
    }

    private function db(): Database
    {
        if (self::$database === null) {
            $this->fail('Las pruebas de integración requieren TEST_DB_HOST.');
        }
        return self::$database;
    }

    private function profile(string $email): UserProfile
    {
        $profile = (new UserRepository($this->db()))->getProfileByEmail($email);
        if ($profile === null) {
            $this->fail("No existe el perfil {$email}.");
        }
        return $profile;
    }

    private static function newPoll(int $userId): Poll
    {
        $poll = new Poll();
        $poll->title = 'Título';
        $poll->description = 'Descripción';
        $poll->position = 1;
        $poll->userId = $userId;
        return $poll;
    }
}
