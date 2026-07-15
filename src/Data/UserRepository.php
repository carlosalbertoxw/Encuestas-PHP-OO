<?php

declare(strict_types=1);

namespace Encuestas\Data;

use Encuestas\Model\User;
use Encuestas\Model\UserProfile;
use PDOException;

/** Acceso a datos de cuentas y perfiles (a_users, a_users_profiles). */
class UserRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Crea la cuenta y su perfil (usuario{id}) en una sola transacción.
     * Devuelve Duplicate si el correo ya existe.
     */
    public function createUser(string $email, string $passwordHash, string $securityStamp): RepositoryResult
    {
        $pdo = $this->db->connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('INSERT INTO a_users(u_email, u_password, u_security_stamp) VALUES(?, ?, ?)');
            $statement->execute([$email, $passwordHash, $securityStamp]);
            $userId = (int) $pdo->lastInsertId();

            $statement = $pdo->prepare('INSERT INTO a_users_profiles(u_p_key, u_p_user_name, u_p_name) VALUES(?, ?, ?)');
            $statement->execute([$userId, 'usuario' . $userId, 'Usuario' . $userId]);

            $pdo->commit();
            return RepositoryResult::Success;
        } catch (PDOException $exception) {
            $pdo->rollBack();
            if (Database::isDuplicateKey($exception)) {
                return RepositoryResult::Duplicate;
            }
            throw $exception;
        }
    }

    public function getUser(int $id): ?User
    {
        $statement = $this->db->connection()->prepare(
            'SELECT u_key, u_email, u_email_confirmed, u_password, u_security_stamp FROM a_users WHERE u_key=?'
        );
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row === false ? null : $this->mapUser($row);
    }

    /** Sello de seguridad actual de la cuenta, o null si no existe. */
    public function getSecurityStamp(int $id): ?string
    {
        $statement = $this->db->connection()->prepare('SELECT u_security_stamp FROM a_users WHERE u_key=?');
        $statement->execute([$id]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    /** Marca el correo del usuario como confirmado. */
    public function confirmEmail(int $id): RepositoryResult
    {
        return $this->executeUpdate('UPDATE a_users SET u_email_confirmed=1 WHERE u_key=?', [$id]);
    }

    public function getProfileByEmail(string $email): ?UserProfile
    {
        return $this->getProfile('u.u_email=?', $email);
    }

    public function getProfileByUserName(string $userName): ?UserProfile
    {
        return $this->getProfile('up.u_p_user_name=?', $userName);
    }

    public function updateName(int $id, string $name): RepositoryResult
    {
        return $this->executeUpdate('UPDATE a_users_profiles SET u_p_name=? WHERE u_p_key=?', [$name, $id]);
    }

    public function updateUserName(int $id, string $userName): RepositoryResult
    {
        return $this->executeUpdate('UPDATE a_users_profiles SET u_p_user_name=? WHERE u_p_key=?', [$userName, $id]);
    }

    public function updateEmail(int $id, string $email): RepositoryResult
    {
        return $this->executeUpdate('UPDATE a_users SET u_email=? WHERE u_key=?', [$email, $id]);
    }

    /** Actualiza solo el hash (p. ej. al regenerarlo con parámetros nuevos); conserva el sello de seguridad. */
    public function updatePassword(int $id, string $passwordHash): RepositoryResult
    {
        return $this->executeUpdate('UPDATE a_users SET u_password=? WHERE u_key=?', [$passwordHash, $id]);
    }

    /** Cambia la contraseña y rota el sello de seguridad para invalidar otras sesiones. */
    public function changePassword(int $id, string $passwordHash, string $newSecurityStamp): RepositoryResult
    {
        return $this->executeUpdate(
            'UPDATE a_users SET u_password=?, u_security_stamp=? WHERE u_key=?',
            [$passwordHash, $newSecurityStamp, $id]
        );
    }

    /** Elimina la cuenta; el esquema borra en cascada perfil, encuestas y respuestas. */
    public function deleteAccount(int $id): RepositoryResult
    {
        $statement = $this->db->connection()->prepare('DELETE FROM a_users WHERE u_key=?');
        $statement->execute([$id]);
        return $statement->rowCount() === 1 ? RepositoryResult::Success : RepositoryResult::NotFound;
    }

    private function getProfile(string $where, string $value): ?UserProfile
    {
        $statement = $this->db->connection()->prepare(
            'SELECT u.u_key, u.u_email, u.u_email_confirmed, u.u_password, u.u_security_stamp, up.u_p_user_name, up.u_p_name '
            . 'FROM a_users AS u JOIN a_users_profiles AS up ON u.u_key = up.u_p_key WHERE ' . $where
        );
        $statement->execute([$value]);
        $row = $statement->fetch();
        return $row === false ? null : $this->mapProfile($row);
    }

    /** @param list<int|string> $parameters */
    private function executeUpdate(string $sql, array $parameters): RepositoryResult
    {
        try {
            $statement = $this->db->connection()->prepare($sql);
            $statement->execute($parameters);
            return $statement->rowCount() >= 1 ? RepositoryResult::Success : RepositoryResult::NotFound;
        } catch (PDOException $exception) {
            if (Database::isDuplicateKey($exception)) {
                return RepositoryResult::Duplicate;
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $row */
    private function mapUser(array $row): User
    {
        $user = new User();
        $user->id = (int) $row['u_key'];
        $user->email = (string) $row['u_email'];
        $user->emailConfirmed = (bool) $row['u_email_confirmed'];
        $user->passwordHash = (string) $row['u_password'];
        $user->securityStamp = (string) $row['u_security_stamp'];
        return $user;
    }

    /** @param array<string, mixed> $row */
    private function mapProfile(array $row): UserProfile
    {
        $profile = new UserProfile();
        $profile->user = $this->mapUser($row);
        $profile->userName = (string) $row['u_p_user_name'];
        $profile->name = (string) $row['u_p_name'];
        return $profile;
    }
}
