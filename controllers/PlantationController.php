<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/PlantationModel.php';
require_once __DIR__ . '/../models/SuiviModel.php';

/** Contrôle les routes et règles métier Plantation/Suivi. */
final class PlantationController
{
    private PlantationModel $plantations;
    private SuiviModel $suivis;

    public function __construct()
    {
        $this->plantations = new PlantationModel();
        $this->suivis = new SuiviModel();
    }

    public function summary(): array
    {
        $plantations = $this->plantations->all();
        $suivis = $this->suivis->all();
        $alerts = array_filter($suivis, static function (array $suivi): bool {
            $state = function_exists('mb_strtolower') ? mb_strtolower((string) $suivi['etat'], 'UTF-8') : strtolower((string) $suivi['etat']);
            return str_contains($state, 'stress') || str_contains($state, 'maladie') || str_contains($state, 'attention') || str_contains($state, 'surveill');
        });
        return [
            'plantations' => count($plantations),
            'suivis' => count($suivis),
            'varietes' => $this->plantations->countVarieties(),
            'alertes' => count($alerts),
            'alertesRecent' => array_slice(array_values($alerts), 0, 3),
            'plantationList' => array_slice($plantations, 0, 4),
            'suiviRecent' => array_slice($suivis, 0, 4),
        ];
    }

    public function index(): void
    {
        $this->render('front/plantations.php', [
            'plantations' => $this->plantations->all(),
            'summary' => $this->summary(),
            'csrfToken' => $this->csrfToken(),
            'flash' => $this->takeFlash(),
        ], 'Mes plantations | Taam Bledi');
    }

    public function show(int $id): void
    {
        $plantation = $this->plantations->find($id);
        if (!$plantation) {
            $this->notFound();
            return;
        }
        $this->render('front/plantation_details.php', [
            'plantation' => $plantation,
            'suivis' => $this->suivis->forPlantation($id),
            'csrfToken' => $this->csrfToken(),
            'flash' => $this->takeFlash(),
        ], 'Détail de plantation | Taam Bledi');
    }

    public function form(?int $id): void
    {
        $existing = $id ? $this->plantations->find($id) : null;
        if ($id && !$existing) {
            $this->notFound();
            return;
        }
        $old = $existing ?? [
            'nom' => '', 'adresse' => '', 'latitude' => '', 'longitude' => '',
            'surface' => '', 'typeSupport' => '', 'dateCreation' => date('Y-m-d'), 'idVariete' => '',
        ];
        $errors = [];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $old = $this->plantationInput($_POST);
            if (!$this->validCsrf($_POST['csrf_token'] ?? null)) {
                $errors[] = 'Votre session de formulaire a expiré. Rechargez la page puis réessayez.';
            } else {
                $errors = $this->validatePlantation($old);
                if (!$errors) {
                    $saved = $id
                        ? $this->plantations->updateById($id, $old)
                        : $this->plantations->create($old);
                    if ($saved) {
                        $this->setFlash($id ? 'Plantation modifiée.' : 'Plantation ajoutée.');
                        $this->redirect($id ? 'index.php?action=plantation_show&id=' . $id : 'index.php?action=plantations');
                    }
                    $errors[] = 'La plantation n’a pas pu être enregistrée.';
                }
            }
        }
        $this->render('front/plantation_edit.php', [
            'old' => $old,
            'recordId' => $id,
            'errors' => $errors,
            'varieties' => $this->plantations->varieties(),
            'csrfToken' => $this->csrfToken(),
            'editing' => $id !== null,
        ], ($id ? 'Modifier' : 'Ajouter') . ' une plantation | Taam Bledi');
    }

    public function delete(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$this->validCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $this->setFlash('Suppression refusée : formulaire invalide ou expiré.');
            $this->redirect('index.php?action=plantations');
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || !$this->plantations->find((int) $id)) {
            http_response_code(404);
            $this->notFound();
            return;
        }
        $this->plantations->deleteById((int) $id);
        $this->setFlash('Plantation supprimée avec ses suivis associés.');
        $this->redirect('index.php?action=plantations');
    }

    public function adminIndex(): void
    {
        $errors = [];
        $old = ['nom' => '', 'adresse' => '', 'latitude' => '', 'longitude' => '', 'surface' => '', 'typeSupport' => '', 'dateCreation' => date('Y-m-d'), 'idVariete' => ''];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $old = $this->plantationInput($_POST);
            if (!$this->validCsrf($_POST['csrf_token'] ?? null)) {
                $errors[] = 'Votre session de formulaire a expiré. Rechargez la page puis réessayez.';
            } else {
                $errors = $this->validatePlantation($old);
                if (!$errors && $this->plantations->create($old)) {
                    $this->setFlash('Plantation ajoutée.');
                    $this->redirect('index.php?action=admin_plantations');
                }
            }
        }
        $this->render('back/plantations.php', [
            'plantations' => $this->plantations->all(),
            'flash' => $this->takeFlash(),
            'errors' => $errors,
            'old' => $old,
            'varieties' => $this->plantations->varieties(),
            'csrfToken' => $this->csrfToken(),
            'suiviCount' => count($this->suivis->all()),
        ], 'Gestion des plantations | Taam Bledi', 'plantations', true);
    }

    public function adminShow(int $id): void
    {
        $plantation = $this->plantations->find($id);
        if (!$plantation) {
            $this->notFound(true);
            return;
        }
        $this->render('back/plantation_details.php', [
            'plantation' => $plantation,
            'suivis' => $this->suivis->forPlantation($id),
            'csrfToken' => $this->csrfToken(),
        ], 'Détail plantation | Administration', 'plantations', true);
    }

    public function adminDeletePlantation(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$this->validCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $this->redirect('index.php?action=admin_plantations');
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || !$this->plantations->find((int) $id)) {
            $this->notFound(true);
            return;
        }
        $this->plantations->deleteById((int) $id);
        $this->setFlash('Plantation et suivis associés supprimés.');
        $this->redirect('index.php?action=admin_plantations');
    }

    private function plantationInput(array $input): array
    {
        $keys = ['nom', 'adresse', 'latitude', 'longitude', 'surface', 'typeSupport', 'dateCreation', 'idVariete'];
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : '';
        }
        return $data;
    }

    private function validatePlantation(array $data): array
    {
        $errors = [];
        foreach (['nom' => 100, 'adresse' => 100] as $key => $max) {
            if ($data[$key] === '' || $this->length($data[$key]) > $max) {
                $errors[] = $key === 'nom' ? 'Le nom est obligatoire (100 caractères maximum).' : 'L’adresse est obligatoire (100 caractères maximum, selon le schéma existant).';
            }
        }
        if (!is_numeric($data['surface']) || (float) $data['surface'] <= 0 || (float) $data['surface'] > 10000000) {
            $errors[] = 'La surface doit être un nombre positif valide.';
        }
        if (!in_array($data['typeSupport'], ['toit', 'balcon', 'jardin'], true)) {
            $errors[] = 'Choisissez un type de support valide.';
        }
        if (!$this->validDate($data['dateCreation'])) {
            $errors[] = 'La date de création doit être une date valide.';
        }
        foreach (['latitude' => [-90, 90], 'longitude' => [-180, 180]] as $key => [$min, $max]) {
            if ($data[$key] !== '' && (!is_numeric($data[$key]) || (float) $data[$key] < $min || (float) $data[$key] > $max)) {
                $errors[] = $key === 'latitude' ? 'La latitude doit être comprise entre -90 et 90.' : 'La longitude doit être comprise entre -180 et 180.';
            }
        }
        if ($data['idVariete'] !== '') {
            $validIds = array_map(static fn(array $row): string => (string) $row['idVariete'], $this->plantations->varieties());
            if (!ctype_digit($data['idVariete']) || !in_array($data['idVariete'], $validIds, true)) {
                $errors[] = 'La variété sélectionnée n’existe pas.';
            }
        }
        return $errors;
    }

    private function validDate(string $value): bool
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
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

    private function notFound(bool $admin = false): void
    {
        http_response_code(404);
        $this->render('not_found.php', [], 'Introuvable | Taam Bledi', $admin ? 'plantations' : 'dashboard', $admin);
    }

    private function render(string $view, array $data, string $title, string $activePage = 'dashboard', bool $admin = false): void
    {
        extract($data, EXTR_SKIP);
        $activePage = $activePage;
        ob_start();
        require __DIR__ . '/../views/' . $view;
        $content = ob_get_clean();
        require __DIR__ . '/../views/' . ($admin ? 'back/layout_admin.php' : 'front/layout.php');
    }
}
