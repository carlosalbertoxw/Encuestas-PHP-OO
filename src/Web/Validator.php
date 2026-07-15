<?php

declare(strict_types=1);

namespace Encuestas\Web;

/**
 * Validación en el servidor de los formularios. La política de contraseñas
 * (mínimo 6, máximo 50) se aplica aquí; las validaciones del navegador
 * (atributos HTML) son solo una mejora de experiencia de usuario.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function required(string $field, string $value): self
    {
        if (trim($value) === '') {
            $this->addError($field, 'Este campo es obligatorio');
        }
        return $this;
    }

    public function email(string $field, string $value): self
    {
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, 'El correo electrónico no es válido');
        }
        return $this;
    }

    public function length(string $field, string $value, int $min, int $max): self
    {
        $length = mb_strlen($value);
        if ($value !== '' && ($length < $min || $length > $max)) {
            $this->addError($field, $min > 0
                ? "Debe tener entre {$min} y {$max} caracteres"
                : "Debe tener como máximo {$max} caracteres");
        }
        return $this;
    }

    public function match(string $field, string $value, string $other): self
    {
        if ($value !== $other) {
            $this->addError($field, 'Los valores no coinciden');
        }
        return $this;
    }

    /** @param non-empty-string $pattern Expresión regular completa, con delimitadores. */
    public function pattern(string $field, string $value, string $pattern, string $message): self
    {
        if ($value !== '' && preg_match($pattern, $value) !== 1) {
            $this->addError($field, $message);
        }
        return $this;
    }

    public function range(string $field, int $value, int $min, int $max): self
    {
        if ($value < $min || $value > $max) {
            $this->addError($field, "Debe estar entre {$min} y {$max}");
        }
        return $this;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> Primer error por campo. */
    public function errors(): array
    {
        return $this->errors;
    }

    private function addError(string $field, string $message): void
    {
        // Se conserva el primer error de cada campo.
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }
}
