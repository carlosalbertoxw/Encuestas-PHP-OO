<?php

declare(strict_types=1);

namespace Encuestas\Data;

use Encuestas\Model\Poll;

/** Acceso a datos de encuestas (a_polls). */
class PollRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Página de encuestas de un usuario, ordenadas por posición.
     *
     * @return PagedResult<Poll>
     */
    public function getPolls(int $userId, int $page, int $pageSize = PagedResult::DEFAULT_PAGE_SIZE): PagedResult
    {
        [$page, $pageSize] = PagedResult::normalize($page, $pageSize);
        $pdo = $this->db->connection();

        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM a_polls WHERE p_user_key=?');
        $countStatement->execute([$userId]);
        $total = (int) $countStatement->fetchColumn();

        $statement = $pdo->prepare(
            'SELECT p_key, p_title, p_description, p_position, p_user_key FROM a_polls WHERE p_user_key=? '
            . 'ORDER BY p_position ASC, p_key ASC LIMIT ? OFFSET ?'
        );
        $statement->bindValue(1, $userId, \PDO::PARAM_INT);
        $statement->bindValue(2, $pageSize, \PDO::PARAM_INT);
        $statement->bindValue(3, ($page - 1) * $pageSize, \PDO::PARAM_INT);
        $statement->execute();
        $polls = [];
        while (($row = $statement->fetch()) !== false) {
            $polls[] = $this->mapPoll($row);
        }
        return new PagedResult($polls, $page, $pageSize, $total);
    }

    /** Una encuesta concreta, validando que pertenezca al usuario. */
    public function getPoll(int $userId, int $pollId): ?Poll
    {
        $statement = $this->db->connection()->prepare(
            'SELECT p_key, p_title, p_description, p_position, p_user_key FROM a_polls WHERE p_user_key=? AND p_key=?'
        );
        $statement->execute([$userId, $pollId]);
        $row = $statement->fetch();
        return $row === false ? null : $this->mapPoll($row);
    }

    /** Una encuesta por id, sin validar propietario (para responderla). */
    public function getPollById(int $pollId): ?Poll
    {
        $statement = $this->db->connection()->prepare(
            'SELECT p_key, p_title, p_description, p_position, p_user_key FROM a_polls WHERE p_key=?'
        );
        $statement->execute([$pollId]);
        $row = $statement->fetch();
        return $row === false ? null : $this->mapPoll($row);
    }

    public function addPoll(Poll $poll): bool
    {
        $statement = $this->db->connection()->prepare(
            'INSERT INTO a_polls(p_title, p_description, p_position, p_user_key) VALUES(?, ?, ?, ?)'
        );
        $statement->execute([$poll->title, $poll->description, $poll->position, $poll->userId]);
        return $statement->rowCount() === 1;
    }

    public function updatePoll(Poll $poll): bool
    {
        $statement = $this->db->connection()->prepare(
            'UPDATE a_polls SET p_title=?, p_description=?, p_position=? WHERE p_key=? AND p_user_key=?'
        );
        $statement->execute([$poll->title, $poll->description, $poll->position, $poll->id, $poll->userId]);
        return $statement->rowCount() === 1;
    }

    public function deletePoll(int $userId, int $pollId): bool
    {
        $statement = $this->db->connection()->prepare('DELETE FROM a_polls WHERE p_key=? AND p_user_key=?');
        $statement->execute([$pollId, $userId]);
        return $statement->rowCount() === 1;
    }

    /** @param array<string, mixed> $row */
    private function mapPoll(array $row): Poll
    {
        $poll = new Poll();
        $poll->id = (int) $row['p_key'];
        $poll->title = (string) $row['p_title'];
        $poll->description = (string) $row['p_description'];
        $poll->position = (int) $row['p_position'];
        $poll->userId = (int) $row['p_user_key'];
        return $poll;
    }
}
