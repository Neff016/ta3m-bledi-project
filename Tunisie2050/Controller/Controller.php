<?php
abstract class Controller
{
    // Renders View/<area>/<view>.php inside View/<area>/layout.php
    protected function render(string $area, string $view, array $data = []): void
    {
        extract($data);
        ob_start();
        require __DIR__ . "/../View/$area/$view.php";
        $content = ob_get_clean();
        require __DIR__ . "/../View/$area/layout.php";
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function notFound(string $message = 'Page introuvable'): void
    {
        http_response_code(404);
        $this->render('FrontOffice', '404', [
            'pageTitle'   => $message,
            'currentPage' => '',
            'message'     => $message,
        ]);
    }
}