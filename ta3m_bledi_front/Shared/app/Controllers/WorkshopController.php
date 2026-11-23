<?php
declare(strict_types=1);

class WorkshopController extends Controller
{
    public function index(): void
    {
        $workshops = (new Workshop())->all(true);
        $this->render('ateliers', ['workshops' => $workshops], 'Ta3m Bledi | Ateliers');
    }

    public function notFound(): void
    {
        $this->render('404', [], 'Page introuvable');
    }
}
