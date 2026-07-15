<?php

declare(strict_types=1);

namespace Encuestas\Web;

use Encuestas\Config;
use Encuestas\Web\Services\AuthService;

/**
 * Render de plantillas PHP dentro del layout compartido. Las plantillas viven
 * en src/Views y reciben sus datos como variables locales; toda salida
 * dinámica debe escaparse con e().
 */
final class View
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, string $title, array $data = [], ?string $message = null): void
    {
        $auth = $this->auth;
        $view = $this;
        extract($data, EXTR_SKIP);

        ob_start();
        require Config::basePath() . '/src/Views/' . $template . '.php';
        $content = (string) ob_get_clean();

        require Config::basePath() . '/src/Views/layout.php';
    }

    /** Ruta de un asset con *cache busting* según su fecha de modificación. */
    public function asset(string $path): string
    {
        $file = Config::basePath() . '/public' . $path;
        $version = is_file($file) ? (string) filemtime($file) : '0';
        return $path . '?v=' . $version;
    }
}
