<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/**
 * Genera y valida tokens firmados (HMAC-SHA256) y con caducidad para confirmar
 * el correo y restablecer la contraseña. Son *stateless*: no se guardan en la
 * BD. El token de restablecimiento incluye el sello de seguridad del usuario,
 * así que se invalida en cuanto la contraseña cambia (uso único).
 */
class TokenService
{
    private const CONFIRM_PURPOSE = 'confirm';
    private const RESET_PURPOSE = 'reset';
    private const CONFIRM_LIFETIME_SECONDS = 24 * 3600;
    private const RESET_LIFETIME_SECONDS = 3600;

    public function __construct(private readonly string $key)
    {
    }

    public function createEmailConfirmationToken(int $userId): string
    {
        return $this->sign([self::CONFIRM_PURPOSE, (string) $userId], self::CONFIRM_LIFETIME_SECONDS);
    }

    public function validateEmailConfirmationToken(string $token): ?int
    {
        $parts = $this->unsign($token, self::CONFIRM_PURPOSE, 1);
        return $parts === null ? null : (int) $parts[0];
    }

    public function createPasswordResetToken(int $userId, string $securityStamp): string
    {
        return $this->sign([self::RESET_PURPOSE, (string) $userId, $securityStamp], self::RESET_LIFETIME_SECONDS);
    }

    /** @return array{userId: int, securityStamp: string}|null */
    public function validatePasswordResetToken(string $token): ?array
    {
        $parts = $this->unsign($token, self::RESET_PURPOSE, 2);
        return $parts === null ? null : ['userId' => (int) $parts[0], 'securityStamp' => $parts[1]];
    }

    /** @param list<string> $fields */
    private function sign(array $fields, int $lifetimeSeconds): string
    {
        $fields[] = (string) (time() + $lifetimeSeconds);
        $payload = implode(':', $fields);
        $signature = hash_hmac('sha256', $payload, $this->key, true);
        return self::base64UrlEncode($payload) . '.' . self::base64UrlEncode($signature);
    }

    /**
     * Devuelve los campos del payload (sin propósito ni caducidad) si la firma
     * es válida y el token no expiró; null en caso contrario.
     *
     * @return list<string>|null
     */
    private function unsign(string $token, string $purpose, int $fieldCount): ?array
    {
        $pieces = explode('.', $token);
        if (count($pieces) !== 2) {
            return null;
        }
        $payload = self::base64UrlDecode($pieces[0]);
        $signature = self::base64UrlDecode($pieces[1]);
        if ($payload === null || $signature === null) {
            return null;
        }
        $expected = hash_hmac('sha256', $payload, $this->key, true);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        // payload = propósito : campos... : expiración
        $parts = explode(':', $payload);
        if (count($parts) !== $fieldCount + 2 || $parts[0] !== $purpose) {
            return null;
        }
        $expiresAt = (int) $parts[count($parts) - 1];
        if ($expiresAt < time()) {
            return null;
        }
        return array_slice($parts, 1, $fieldCount);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): ?string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}
