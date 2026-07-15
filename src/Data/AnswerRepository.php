<?php

declare(strict_types=1);

namespace Encuestas\Data;

use Encuestas\Model\Answer;
use PDOException;

/** Acceso a datos de respuestas (a_answers). */
class AnswerRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Página de respuestas de una encuesta (más recientes primero), con el
     * nombre de usuario de cada autor.
     *
     * @return PagedResult<Answer>
     */
    public function getAnswersForPoll(int $pollId, int $page, int $pageSize): PagedResult
    {
        $page = max(1, $page);
        $pageSize = $pageSize < 1 ? 10 : $pageSize;

        $pdo = $this->db->connection();

        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM a_answers WHERE a_poll_key=?');
        $countStatement->execute([$pollId]);
        $total = (int) $countStatement->fetchColumn();

        $statement = $pdo->prepare(
            'SELECT a.a_key, a.a_stars, a.a_comment, a.a_poll_key, a.a_user_key, up.u_p_user_name '
            . 'FROM a_answers AS a JOIN a_users_profiles AS up ON a.a_user_key = up.u_p_key '
            . 'WHERE a.a_poll_key=? ORDER BY a.a_key DESC LIMIT ? OFFSET ?'
        );
        $statement->bindValue(1, $pollId, \PDO::PARAM_INT);
        $statement->bindValue(2, $pageSize, \PDO::PARAM_INT);
        $statement->bindValue(3, ($page - 1) * $pageSize, \PDO::PARAM_INT);
        $statement->execute();

        $answers = [];
        while (($row = $statement->fetch()) !== false) {
            $answer = new Answer();
            $answer->id = (int) $row['a_key'];
            $answer->stars = (int) $row['a_stars'];
            $answer->comment = (string) $row['a_comment'];
            $answer->pollId = (int) $row['a_poll_key'];
            $answer->userId = (int) $row['a_user_key'];
            $answer->userName = (string) $row['u_p_user_name'];
            $answers[] = $answer;
        }

        return new PagedResult($answers, $page, $pageSize, $total);
    }

    /** Registra una respuesta; Duplicate si el usuario ya respondió esa encuesta. */
    public function addAnswer(Answer $answer): RepositoryResult
    {
        try {
            $statement = $this->db->connection()->prepare(
                'INSERT INTO a_answers(a_stars, a_comment, a_poll_key, a_user_key) VALUES(?, ?, ?, ?)'
            );
            $statement->execute([$answer->stars, $answer->comment, $answer->pollId, $answer->userId]);
            return $statement->rowCount() === 1 ? RepositoryResult::Success : RepositoryResult::NotFound;
        } catch (PDOException $exception) {
            if (Database::isDuplicateKey($exception)) {
                return RepositoryResult::Duplicate;
            }
            throw $exception;
        }
    }
}
