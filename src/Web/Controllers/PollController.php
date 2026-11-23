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
            'result' => $this->polls->getPolls($this->auth->userId(), $this->pageNumber()),
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
        [$poll, $errors] = $this->pollFromForm();
        if ($errors !== []) {
            // Se vuelve a mostrar el formulario con lo escrito y el error de cada campo.
            $data = ['poll' => $poll, 'errors' => $errors];
            $this->view->render('poll/form', 'Agregar encuesta', $data, Messages::VALIDATION_ERROR);
            return;
        }
        Flash::set($this->polls->addPoll($poll) ? Messages::POLL_SAVED : Messages::POLL_SAVE_ERROR);
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
        [$poll, $errors] = $this->pollFromForm();
        if ($poll->id < 1) {
            throw new NotFoundException('Poll/Update');
        }
        if ($errors !== []) {
            $data = ['poll' => $poll, 'errors' => $errors];
            $this->view->render('poll/form', 'Editar encuesta', $data, Messages::VALIDATION_ERROR);
            return;
        }
        Flash::set($this->polls->updatePoll($poll) ? Messages::POLL_UPDATED : Messages::UPDATE_ERROR);
        $this->redirect('/Poll/Index');
    }

    public function delete(int $id): void
    {
        if ($id < 1) {
            Flash::set(Messages::VALIDATION_ERROR);
        } elseif ($this->polls->deletePoll($this->auth->userId(), $id)) {
            $this->logger->info('Encuesta eliminada', ['pollId' => $id, 'userId' => $this->auth->userId()]);
            Flash::set(Messages::POLL_DELETED);
        } else {
            Flash::set(Messages::POLL_DELETE_ERROR);
        }
        $this->redirect('/Poll/Index');
    }

    /**
     * Construye la encuesta del usuario actual con lo enviado y la valida.
     *
     * @return array{0: Poll, 1: array<string, string>} la encuesta y los errores por campo (vacío si es válida)
     */
    private function pollFromForm(): array
    {
        $poll = new Poll();
        $poll->id = (int) $this->input('Id');
        $poll->title = $this->input('Title');
        $poll->description = $this->input('Description');
        $poll->position = (int) $this->input('Position');
        $poll->userId = $this->auth->userId();

        $validator = (new Validator())
            ->required('Title', $poll->title)->length('Title', $poll->title, 0, 250)
            ->required('Description', $poll->description)->length('Description', $poll->description, 0, 500)
            ->range('Position', $poll->position, 1, 999999);
        return [$poll, $validator->errors()];
    }
}
