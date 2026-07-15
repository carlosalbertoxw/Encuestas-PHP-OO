<?php

declare(strict_types=1);

namespace Encuestas\Web\Controllers;

/** Páginas de error genéricas (excepciones no controladas y 404). */
final class ErrorController extends Controller
{
    public function serverError(): void
    {
        http_response_code(500);
        $this->view->render('shared/error', 'Error');
    }

    public function notFound(): void
    {
        http_response_code(404);
        $this->view->render('shared/error404', 'Error 404');
    }
}
