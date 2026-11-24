<?php
require_once __DIR__ . '/AdminController.php';

class ProjectionsController extends AdminController
{
    public function index(): void
    {
        $this->requireLogin();
        $villes = [];
        foreach (Store::all('villes') as $v) {
            $villes[(int)$v['idVille']] = $v['nom'];
        }
        $projs = Store::all('projections');
        usort($projs, fn($a, $b) => [$a['idVille'], $a['annee']] <=> [$b['idVille'], $b['annee']]);
        $this->renderAdmin('projections', 'projections', 'Projections', compact('projs', 'villes'));
    }

    public function form(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $p  = $id ? $this->findRow(Store::all('projections'), 'idProjection', $id) : null;
        if ($id && !$p) {
            $this->flash('Projection introuvable.', 'danger');
            $this->redirect('projections');
        }
        [$errors, $old] = $this->takeFormState();
        $this->renderAdmin('projection_form', 'projections', $id ? 'Modifier une projection' : 'Nouvelle projection', [
            'p' => array_merge($p ?? [], $old), 'villes' => Store::all('villes'), 'errors' => $errors,
        ]);
    }

    public function save(): void
    {
        $this->requireLogin();
        $this->requirePost('projections');

        $id  = (int)($_POST['idProjection'] ?? 0);
        $row = [
            'idProjection'     => $id,
            'annee'            => trim((string)($_POST['annee'] ?? '')),
            'temperatureMoy'   => trim((string)($_POST['temperatureMoy'] ?? '')),
            'pluviometrie'     => trim((string)($_POST['pluviometrie'] ?? '')),
            'stressHydrique'   => (string)($_POST['stressHydrique'] ?? ''),
            'culturesMenacees' => trim((string)($_POST['culturesMenacees'] ?? '')),
            'description'      => trim((string)($_POST['description'] ?? '')),
            'idVille'          => (int)($_POST['idVille'] ?? 0),
        ];

        $all = Store::all('projections');
        $errors = [];
        if ($id && !$this->findRow($all, 'idProjection', $id)) $errors[] = 'Projection introuvable.';
        if (!$this->findRow(Store::all('villes'), 'idVille', $row['idVille'])) $errors[] = 'Région invalide.';
        if (!ctype_digit($row['annee']) || (int)$row['annee'] < 2000 || (int)$row['annee'] > 2100)
            $errors[] = 'Année invalide (2000 à 2100).';
        if (!is_numeric($row['temperatureMoy']) || $row['temperatureMoy'] < -10 || $row['temperatureMoy'] > 60)
            $errors[] = 'Température invalide (-10 à 60 °C).';
        if (!is_numeric($row['pluviometrie']) || $row['pluviometrie'] < 0 || $row['pluviometrie'] > 3000)
            $errors[] = 'Pluviométrie invalide (0 à 3000 mm).';
        if (!in_array($row['stressHydrique'], STRESS, true)) $errors[] = 'Stress hydrique invalide.';
        if (mb_strlen($row['culturesMenacees']) > 200) $errors[] = 'Liste de cultures trop longue (200 caractères max).';
        if (mb_strlen($row['description']) > 300) $errors[] = 'Description trop longue (300 caractères max).';
        if (!$errors) {
            foreach ($all as $p) {
                if ((int)$p['idVille'] === $row['idVille'] && (int)$p['annee'] === (int)$row['annee']
                    && (int)$p['idProjection'] !== $id) {
                    $errors[] = 'Cette région a déjà une projection pour cette année.';
                }
            }
        }
        if ($errors) {
            $this->back('projection-form', $id, $errors, $row);
        }
        $row['annee']          = (int)$row['annee'];
        $row['temperatureMoy'] = (float)$row['temperatureMoy'];
        $row['pluviometrie']   = (float)$row['pluviometrie'];

        if ($id) {
            foreach ($all as $k => $r) {
                if ((int)$r['idProjection'] === $id) $all[$k] = $row;
            }
        } else {
            $row['idProjection'] = Store::nextId('projections', 'idProjection');
            $all[] = $row;
        }
        Store::replace('projections', $all);
        $this->flash('Projection enregistrée.');
        $this->redirect('projections');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requirePost('projections');
        $id = (int)($_POST['id'] ?? 0);
        Store::replace('projections', array_filter(Store::all('projections'), fn($p) => (int)$p['idProjection'] !== $id));
        $this->flash('Projection supprimée.');
        $this->redirect('projections');
    }
}
