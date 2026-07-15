<?php

declare(strict_types=1);

namespace Encuestas\Web\Controllers;

use Encuestas\Data\AnswerRepository;
use Encuestas\Data\PollRepository;
use Encuestas\Data\RepositoryResult;
use Encuestas\Model\Answer;
use Encuestas\Web\Flash;
use Encuestas\Web\NotFoundException;
use Encuestas\Web\RequestContext;
use Encuestas\Web\Services\AuthService;
use Encuestas\Web\Services\Messages;
use Encuestas\Web\Validator;
use Encuestas\Web\View;

/** Responder encuestas y consultar las respuestas recibidas. */
final class AnswerController extends Controller
{
    private const PAGE_SIZE = 10;

    public function __construct(
        View $view,
        AuthService $auth,
        RequestContext $request,
        private readonly PollRepository $polls,
        private readonly AnswerRepository $answers,
    ) {
        parent::__construct($view, $auth, $request);
    }

    /** Formulario para responder una encuesta (propia o de otro usuario). */
    public function add(int $id): void
    {
        $poll = $this->polls->getPollById($id);
        if ($poll === null) {
            throw new NotFoundException((string) $id);
        }
        $this->view->render('answer/form', 'Responder encuesta', ['poll' => $poll], Flash::pull());
    }

    public function addPost(int $id): void
    {
        $poll = $this->polls->getPollById($id);
        if ($poll === null) {
            throw new NotFoundException((string) $id);
        }

        $stars = (int) $this->input('Stars');
        $comment = $this->input('Comment');
        $validator = (new Validator())
            ->range('Stars', $stars, 1, 5)
            ->length('Comment', $comment, 0, 1000);
        if (!$validator->isValid()) {
            Flash::set(Messages::VALIDATION_ERROR);
            $this->redirect('/Answer/Add/' . $id);
            return;
        }

        $answer = new Answer();
        $answer->stars = $stars;
        $answer->comment = $comment;
        $answer->pollId = $id;
        $answer->userId = $this->auth->userId();

        Flash::set(match ($this->answers->addAnswer($answer)) {
            RepositoryResult::Success => Messages::ANSWER_SAVED,
            RepositoryResult::Duplicate => Messages::ANSWER_DUPLICATE,
            default => Messages::ANSWER_ERROR,
        });
        $this->redirect('/Poll/Index');
    }

    /** Respuestas recibidas por una encuesta; solo visibles para su propietario. */
    public function answers(int $id): void
    {
        $poll = $this->polls->getPoll($this->auth->userId(), $id);
        if ($poll === null) {
            throw new NotFoundException((string) $id);
        }
        $page = max(1, (int) ($this->query('page') ?? '1'));
        $this->view->render('answer/answers', "Respuestas: {$poll->title}", [
            'pollId' => $id,
            'result' => $this->answers->getAnswersForPoll($id, $page, self::PAGE_SIZE),
        ], Flash::pull());
    }
}
