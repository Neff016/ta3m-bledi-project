<?php
require_once __DIR__ . '/AdminController.php';

class DashboardController extends AdminController
{
    public function index(): void
    {
        $this->requireLogin();
        $villes = Store::all('villes');
        $projs  = Store::all('projections');
        $users  = Utilisateur::all();
        $critiques = count(array_filter($projs, fn($p) => $p['stressHydrique'] === 'Critique'));
        $this->renderAdmin('dashboard', 'dashboard', 'Tableau de bord', compact('villes', 'projs', 'users', 'critiques'));
    }

    public function reset(): void
    {
        $this->requireLogin();
        $this->requirePost('dashboard');
        Store::reset();
        $this->flash('Régions et projections réinitialisées depuis les seeds.');
        $this->redirect('dashboard');
    }
}
