<?php

declare(strict_types=1);

namespace Encuestas\Model;

/** Perfil público asociado a una cuenta de usuario. */
final class UserProfile
{
    public User $user;
    public string $userName = '';
    public string $name = '';

    public function __construct()
    {
        $this->user = new User();
    }
}
