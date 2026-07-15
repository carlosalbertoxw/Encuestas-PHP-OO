<?php

declare(strict_types=1);

namespace Encuestas\Infrastructure;

use Encuestas\Data\AnswerRepository;
use Encuestas\Data\PollRepository;
use Encuestas\Data\UserRepository;
use Encuestas\Model\Answer;
use Encuestas\Model\Poll;
use Encuestas\Web\Services\PasswordService;

/**
 * Inserta datos de demostración cuando la base de datos está vacía. Solo se
 * invoca en el entorno development; los datos nunca deben llegar a producción.
 * Las contraseñas se hashean con PasswordService, igual que en el registro real.
 */
final class DevDataSeeder
{
    private const DEMO_EMAIL = 'demo@encuestas.dev';
    private const DEMO_PASSWORD = 'demo1234';
    private const ANA_EMAIL = 'ana@encuestas.dev';
    private const ANA_PASSWORD = 'ana12345';

    public function __construct(
        private readonly UserRepository $users,
        private readonly PollRepository $polls,
        private readonly AnswerRepository $answers,
        private readonly PasswordService $passwords,
        private readonly Logger $logger,
    ) {
    }

    public function seed(): void
    {
        if ($this->users->getProfileByEmail(self::DEMO_EMAIL) !== null) {
            return;
        }

        // Cuenta principal con una encuesta de ejemplo.
        $this->users->createUser(self::DEMO_EMAIL, $this->passwords->hash(self::DEMO_PASSWORD), self::newStamp());
        $demo = $this->users->getProfileByEmail(self::DEMO_EMAIL);
        if ($demo === null) {
            return;
        }
        $this->users->confirmEmail($demo->user->id);
        $this->users->updateUserName($demo->user->id, 'demo');
        $this->users->updateName($demo->user->id, 'Usuario Demo');

        $poll = new Poll();
        $poll->title = '¿Qué te parece la aplicación?';
        $poll->description = 'Cuéntanos tu experiencia usando Encuestas.';
        $poll->position = 1;
        $poll->userId = $demo->user->id;
        $this->polls->addPoll($poll);

        // Segunda cuenta que responde la encuesta de la primera.
        $this->users->createUser(self::ANA_EMAIL, $this->passwords->hash(self::ANA_PASSWORD), self::newStamp());
        $ana = $this->users->getProfileByEmail(self::ANA_EMAIL);
        if ($ana === null) {
            return;
        }
        $this->users->confirmEmail($ana->user->id);
        $this->users->updateUserName($ana->user->id, 'ana');
        $this->users->updateName($ana->user->id, 'Ana');

        $demoPolls = $this->polls->getPolls($demo->user->id);
        if ($demoPolls !== []) {
            $answer = new Answer();
            $answer->stars = 5;
            $answer->comment = '¡Muy fácil de usar!';
            $answer->pollId = $demoPolls[0]->id;
            $answer->userId = $ana->user->id;
            $this->answers->addAnswer($answer);
        }

        $this->logger->info('Datos de demostración insertados (solo development).');
    }

    private static function newStamp(): string
    {
        return \Encuestas\Web\Services\AuthService::newSecurityStamp();
    }
}
