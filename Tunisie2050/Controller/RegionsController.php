<?php
require_once __DIR__ . '/AdminController.php';

class RegionsController extends AdminController
{
    public function index(): void
    {
        $this->requireLogin();
        $this->renderAdmin('regions', 'regions', 'Régions', ['villes' => Store::all('villes')]);
    }

    public function form(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $r  = $id ? $this->findRow(Store::all('villes'), 'idVille', $id) : null;
        if ($id && !$r) {
            $this->flash('Région introuvable.', 'danger');
            $this->redirect('regions');
        }
        [$errors, $old] = $this->takeFormState();
        $this->renderAdmin('region_form', 'regions', $id ? 'Modifier une région' : 'Nouvelle région',
            ['r' => array_merge($r ?? [], $old), 'errors' => $errors]);
    }

    public function save(): void
    {
        $this->requireLogin();
        $this->requirePost('regions');

        $id  = (int)($_POST['idVille'] ?? 0);
        $row = [
            'idVille'     => $id,
            'nom'         => trim((string)($_POST['nom'] ?? '')),
            'gouvernorat' => trim((string)($_POST['gouvernorat'] ?? '')),
            'latitude'    => trim((string)($_POST['latitude'] ?? '')),
            'longitude'   => trim((string)($_POST['longitude'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'image'       => trim((string)($_POST['image'] ?? '')),
            'idAdmin'     => (int)$this->user['idUtilisateur'],
        ];

        $rows = Store::all('villes');
        $errors = [];
        if ($id && !$this->findRow($rows, 'idVille', $id)) $errors[] = 'Région introuvable.';
        if ($row['nom'] === '' || mb_strlen($row['nom']) > 60) $errors[] = 'Le nom est obligatoire (60 caractères max).';
        if ($row['gouvernorat'] === '' || mb_strlen($row['gouvernorat']) > 60)
            $errors[] = 'Le gouvernorat est obligatoire (60 caractères max).';
        if (!is_numeric($row['latitude']) || $row['latitude'] < -90 || $row['latitude'] > 90)
            $errors[] = 'Latitude invalide (-90 à 90).';
        if (!is_numeric($row['longitude']) || $row['longitude'] < -180 || $row['longitude'] > 180)
            $errors[] = 'Longitude invalide (-180 à 180).';
        if (mb_strlen($row['description']) > 300) $errors[] = 'Description trop longue (300 caractères max).';
        if ($row['image'] !== '' && !preg_match('/^[\w\-]+\.(jpg|jpeg|png|webp)$/i', $row['image']))
            $errors[] = 'Image invalide (ex. tunis.jpg).';
        foreach ($rows as $other) {
            if ((int)$other['idVille'] !== $id && mb_strtolower($other['nom']) === mb_strtolower($row['nom'])) {
                $errors[] = 'Une région porte déjà ce nom.';
            }
        }
        if ($errors) {
            $this->back('region-form', $id, $errors, $row);
        }
        $row['latitude']  = (float)$row['latitude'];
        $row['longitude'] = (float)$row['longitude'];

        if ($id) {
            foreach ($rows as $k => $r) {
                if ((int)$r['idVille'] === $id) {
                    $row['idAdmin'] = $r['idAdmin'] ?? $row['idAdmin'];
                    $rows[$k] = $row;
                }
            }
        } else {
            $row['idVille'] = Store::nextId('villes', 'idVille');
            $rows[] = $row;
        }
        Store::replace('villes', $rows);
        $this->flash('Région enregistrée.');
        $this->redirect('regions');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requirePost('regions');
        $id = (int)($_POST['id'] ?? 0);
        Store::replace('villes', array_filter(Store::all('villes'), fn($r) => (int)$r['idVille'] !== $id));
        // Projections of a deleted region would be orphans: remove them too
        Store::replace('projections', array_filter(Store::all('projections'), fn($p) => (int)$p['idVille'] !== $id));
        $this->flash('Région et ses projections supprimées.');
        $this->redirect('regions');
    }
}
