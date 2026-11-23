<?php
declare(strict_types=1);

abstract class Controller
{
    protected function render(string $view, array $data = [], string $title = 'Ta3m Bledi'): void
    {
        extract($data, EXTR_SKIP);
        $pageTitle = $title;
        $currentPage = $_GET['page'] ?? 'ateliers';

        ob_start();
        require __DIR__ . '/../Views/pages/' . $view . '.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout.php';
    }
}
