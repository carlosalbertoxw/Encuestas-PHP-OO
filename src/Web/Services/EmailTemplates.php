<?php

declare(strict_types=1);

namespace Encuestas\Web\Services;

/** Cuerpos HTML de los correos, con el contenido escapado. */
final class EmailTemplates
{
    public static function text(string $text): string
    {
        return '<p>' . e($text) . '</p>';
    }

    public static function link(string $text, string $url): string
    {
        $href = e($url);
        return '<p>' . e($text) . "</p><p><a href=\"{$href}\">{$href}</a></p>";
    }
}
