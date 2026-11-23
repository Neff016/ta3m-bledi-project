<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/PlantationModel.php';
require_once __DIR__ . '/../models/SuiviModel.php';

/** Contrôleur dédié aux opérations du module Suivi. */
final class SuiviController
{
    private PlantationModel $plantations;
    private SuiviModel $suivis;

    public function __construct()
    {
        $this->plantations = new PlantationModel();
        $this->suivis = new SuiviModel();
    }

    public function index(): void
    {
        $plantationId = filter_var($_GET['plantation'] ?? null, FILTER_VALIDATE_INT);
        $plantation = $plantationId ? $this->plantations->find((int) $plantationId) : null;
        if ($plantationId && !$plantation) {
            $this->notFound();
            return;
        }
        $this->render('front/suivis.php', [
            'suivis' => $plantation ? $this->suivis->forPlantation((int) $plantationId) : $this->suivis->all(),
            'plantation' => $plantation,
            'csrfToken' => $this->csrfToken(),
        ], 'Mes suivis | Taam Bledi');
    }

    public function form(?int $id): void
    {
        $existing = $id ? $this->suivis->find($id) : null;
        if ($id && !$existing) {
            $this->notFound();
            return;
        }

        // À la modification, la plantation provient du suivi chargé depuis la base.
        // À la création, l'identifiant transmis doit désigner une plantation existante.
        $plantationId = $existing
            ? (int) $existing['idPlantation']
            : (int) ($_GET['plantation'] ?? $_POST['idPlantation'] ?? 0);
        $plantation = $this->plantations->find($plantationId);
        if (!$plantation) {
            $this->notFound();
            return;
        }

        $old = $existing ?? [
            'dateSuivi' => date('Y-m-d'), 'photo' => '', 'etat' => '', 'arrosage' => '',
            'observation' => '', 'diagnosticIA' => '',
        ];
        $errors = [];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $old = $this->input($_POST);
            if (!$this->validCsrf($_POST['csrf_token'] ?? null)) {
                $errors[] = 'Votre session de formulaire a expiré. Rechargez la page puis réessayez.';
            } else {
                $errors = $this->validate($old);
                if (!$errors) {
                    $saved = $id
                        ? $this->suivis->updateById($id, $old, $plantationId)
                        : $this->suivis->create($old, $plantationId);
                    if ($saved) {
                        $this->setFlash($id ? 'Suivi modifié.' : 'Suivi ajouté.');
                        $this->redirect('index.php?action=plantation_show&id=' . $plantationId);
                    }
                    $errors[] = 'Le suivi n’a pas pu être enregistré.';
                }
            }
        }

        $this->render('front/suivi_edit.php', [
            'old' => $old,
            'recordId' => $id,
            'errors' => $errors,
            'plantation' => $plantation,
            'csrfToken' => $this->csrfToken(),
            'editing' => $id !== null,
        ], ($id ? 'Modifier' : 'Ajouter') . ' un suivi | Taam Bledi');
    }

    public function delete(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$this->validCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $this->setFlash('Suppression refusée : formulaire invalide ou expiré.');
            $this->redirect('index.php?action=plantations');
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $suivi = $id ? $this->suivis->find((int) $id) : null;
        if (!$suivi) {
            $this->notFound();
            return;
        }
        $this->suivis->deleteById((int) $id);
        $this->setFlash('Suivi supprimé.');
        $this->redirect('index.php?action=plantation_show&id=' . (int) $suivi['idPlantation']);
    }

    public function adminIndex(): void
    {
        $this->render('back/suivis.php', [
            'suivis' => $this->suivis->all(),
            'csrfToken' => $this->csrfToken(),
            'flash' => $this->takeFlash(),
        ], 'Gestion des suivis | Taam Bledi', 'suivis', true);
    }

    public function adminDelete(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$this->validCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $this->redirect('index.php?action=admin_suivis');
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(404);
            $this->redirect('index.php?action=admin_suivis');
        }
        $this->suivis->deleteById((int) $id);
        $this->setFlash('Suivi supprimé.');
        $this->redirect('index.php?action=admin_suivis');
    }

    private function input(array $input): array
    {
        $keys = ['dateSuivi', 'photo', 'etat', 'arrosage', 'observation', 'diagnosticIA'];
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = isset($input[$key]) && is_scalar($input[$key])
                ? trim((string) $input[$key])
                : '';
        }
        return $data;
    }

    private function validate(array $data): array
    {
        $errors = [];
        $date = DateTime::createFromFormat('!Y-m-d', $data['dateSuivi']);
        if (!$date || $date->format('Y-m-d') !== $data['dateSuivi']) {
            $errors[] = 'La date du suivi est obligatoire et doit être valide.';
        }
        if ($data['etat'] === '' || $this->length($data['etat']) > 100) {
            $errors[] = 'L’état est obligatoire (100 caractères maximum).';
        }
        foreach (['photo' => 500, 'arrosage' => 100, 'observation' => 2000, 'diagnosticIA' => 2000] as $key => $max) {
            if ($this->length($data[$key]) > $max) {
                $errors[] = 'Le champ ' . $key . ' dépasse la longueur maximale de ' . $max . ' caractères.';
            }
        }
        return $errors;
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }

    private function validCsrf(mixed $token): bool
    {
        return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function setFlash(string $message): void
    {
        $_SESSION['plantation_flash'] = $message;
    }

    private function takeFlash(): ?string
    {
        $message = $_SESSION['plantation_flash'] ?? null;
        unset($_SESSION['plantation_flash']);
        return is_string($message) ? $message : null;
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        $this->render('not_found.php', [], 'Introuvable | Taam Bledi');
    }

    private function render(string $view, array $data, string $title, string $activePage = 'dashboard', bool $admin = false): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/../views/' . $view;
        $content = ob_get_clean();
        require __DIR__ . '/../views/' . ($admin ? 'back/layout_admin.php' : 'front/layout.php');
    }
}
