<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/** Mensajes de usuario centralizados para no repetir literales entre controladores. */
final class Messages
{
    public const VALIDATION_ERROR = 'Ocurrió un error en la validación de los datos';
    public const WRONG_PASSWORD = 'La contraseña es incorrecta';
    public const UPDATE_OK = 'Los datos se actualizaron exitosamente';
    public const UPDATE_ERROR = 'Ocurrió un error al actualizar los datos';

    public const INVALID_CREDENTIALS = 'Correo o contraseña incorrectos';
    // Mismo mensaje tanto si el correo ya existía como si el registro procedió,
    // para no revelar qué correos tienen cuenta.
    public const REGISTER_ACKNOWLEDGED = 'Si los datos son válidos, te enviamos un correo para confirmar la cuenta.';
    public const USER_NAME_TAKEN = 'El nombre de usuario no está disponible';
    public const EMAIL_NOT_CONFIRMED = 'Debes confirmar tu correo antes de iniciar sesión. Revisa tu bandeja de entrada.';
    public const ACCOUNT_LOCKED = 'Demasiados intentos fallidos. Intenta de nuevo en unos minutos.';
    public const EMAIL_CONFIRMED = 'Tu correo fue confirmado. Ya puedes iniciar sesión.';
    public const EMAIL_CONFIRMATION_INVALID = 'El enlace de confirmación no es válido o expiró.';
    public const PASSWORD_RESET_SENT =
        'Si el correo corresponde a una cuenta, te enviamos un enlace para restablecer la contraseña.';
    public const PASSWORD_RESET_OK = 'Tu contraseña se restableció. Ya puedes iniciar sesión.';
    public const PASSWORD_RESET_INVALID = 'El enlace para restablecer la contraseña no es válido o expiró.';
    public const TOO_MANY_REQUESTS = 'Demasiados intentos. Espera un minuto y vuelve a intentarlo.';

    public const POLL_SAVED = 'Los datos se guardaron exitosamente';
    public const POLL_UPDATED = 'Los datos se actualizaron exitosamente';
    public const POLL_DELETED = 'Los datos se borraron exitosamente';
    public const POLL_SAVE_ERROR = 'Ocurrió un error al guardar los datos';
    public const POLL_DELETE_ERROR = 'Ocurrió un error al borrar los datos';

    public const ANSWER_SAVED = 'La respuesta se guardó exitosamente';
    public const ANSWER_ERROR = 'Ocurrió un error al guardar la respuesta';
    public const ANSWER_DUPLICATE = 'Ya habías respondido esta encuesta';

    public const ACCOUNT_DELETED = 'La cuenta se eliminó exitosamente';
    public const ACCOUNT_DELETE_ERROR = 'Ocurrió un error al eliminar la cuenta';
}
