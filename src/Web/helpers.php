<?php

declare(strict_types=1);

// Helpers globales para las plantillas.

if (!function_exists('e')) {
    /** Escapa texto para HTML; todas las salidas dinámicas de las vistas pasan por aquí. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
