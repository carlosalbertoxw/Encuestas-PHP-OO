<?php

declare(strict_types=1);

namespace Encuestas\Web\Controllers;

use Encuestas\Data\PollRepository;
use Encuestas\Infrastructure\Logger;
use Encuestas\Model\Poll;
use Encuestas\Web\Flash;
use Encuestas\Web\NotFoundException;
use Encuestas\Web\RequestContext;
use Encuestas\Web\Services\AuthService;
use Encuestas\Web\Services\Messages;
use Encuestas\Web\Validator;
use Encuestas\Web\View;

/** Tablero y CRUD de encuestas del usuario autenticado. */
final class PollController extends Controller
{
    public function __construct(
        View $view,
        AuthService $auth,
        RequestContext $request,
        private readonly PollRepository $polls,
        private readonly Logger $logger,
    ) {
        parent::__construct($view, $auth, $request);
    }

    public function index(): void
    {
        $this->view->render('poll/index', 'Tablero', [
            'polls' => $this->polls->getPolls($this->auth->userId()),
        ], Flash::pull());
    }

    public function add(): void
    {
        $poll = new Poll();
        $poll->position = 1;
        $this->view->render('poll/form', 'Agregar encuesta', ['poll' => $poll]);
    }

    public function addPost(): void
    {
        $poll = $this->pollFromForm();
        if ($poll === null) {
            Flash::set(Messages::VALIDATION_ERROR);
        } else {
            Flash::set($this->polls->addPoll($poll) ? Messages::POLL_SAVED : Messages::POLL_SAVE_ERROR);
        }
        $this->redirect('/Poll/Index');
    }

    public function edit(int $id): void
    {
        $poll = $this->polls->getPoll($this->auth->userId(), $id);
        if ($poll === null) {
            throw new NotFoundException((string) $id);
        }
        $this->view->render('poll/form', 'Editar encuesta', ['poll' => $poll]);
    }

    public function update(): void
    {
        $poll = $this->pollFromForm();
        if ($poll === null || $poll->id < 1) {
            Flash::set(Messages::VALIDATION_ERROR);
        } else {
            Flash::set($this->polls->updatePoll($poll) ? Messages::POLL_UPDATED : Messages::UPDATE_ERROR);
        }
        $this->redirect('/Poll/Index');
    }

    public function delete(int $id): void
    {
        if ($id < 1) {
            Flash::set(Messages::VALIDATION_ERROR);
        } elseif ($this->polls->deletePoll($this->auth->userId(), $id)) {
            $this->logger->info("Encuesta {$id} eliminada por el usuario {$this->auth->userId()}");
            Flash::set(Messages::POLL_DELETED);
        } else {
            Flash::set(Messages::POLL_DELETE_ERROR);
        }
        $this->redirect('/Poll/Index');
    }

    /** Valida el formulario y construye la encuesta del usuario actual, o null si es inválido. */
    private function pollFromForm(): ?Poll
    {
        $title = $this->input('Title');
        $description = $this->input('Description');
        $position = (int) $this->input('Position');

        $validator = (new Validator())
            ->required('Title', $title)->length('Title', $title, 0, 250)
            ->required('Description', $description)->length('Description', $description, 0, 500)
            ->range('Position', $position, 1, 999999);
        if (!$validator->isValid()) {
            return null;
        }

        $poll = new Poll();
        $poll->id = (int) $this->input('Id');
        $poll->title = $title;
        $poll->description = $description;
        $poll->position = $position;
        $poll->userId = $this->auth->userId();
        return $poll;
    }
}
