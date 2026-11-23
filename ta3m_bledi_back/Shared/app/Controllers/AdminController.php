<?php
declare(strict_types=1);

class AdminController extends Controller
{
    public function dashboard(): void
    {
        require_admin();
        $stats = (new Workshop())->stats();
        $this->render('admin/dashboard', ['stats' => $stats], 'Administration');
    }

    public function workshops(): void
    {
        require_admin();
        $workshops = (new Workshop())->all(false);
        $this->render('admin/ateliers', ['workshops' => $workshops], 'Gestion des ateliers');
    }

    public function saveWorkshop(): void
    {
        require_admin();
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'workshop_date' => (string) ($_POST['workshop_date'] ?? ''),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'capacity' => max(1, (int) ($_POST['capacity'] ?? 1)),
            'icon' => trim((string) ($_POST['icon'] ?? '🌱')),
            'status' => in_array($_POST['status'] ?? 'active', ['active', 'draft'], true) ? $_POST['status'] : 'active',
        ];
        if ($data['title'] === '' || $data['category'] === '' || $data['description'] === '' || $data['location'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['workshop_date'])) {
            flash('error', 'Tous les champs obligatoires doivent être remplis correctement.');
            redirect('index.php?page=admin-ateliers');
        }
        $model = new Workshop();
        if ($id > 0) {
            $model->update($id, $data);
            flash('success', 'Atelier modifié.');
        } else {
            $model->create($data);
            flash('success', 'Atelier ajouté.');
        }
        redirect('index.php?page=admin-ateliers');
    }

    public function deleteWorkshop(): void
    {
        require_admin();
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            (new Workshop())->delete($id);
            flash('success', 'Atelier supprimé.');
        }
        redirect('index.php?page=admin-ateliers');
    }

    public function registrations(): void
    {
        require_admin();
        $registrations = (new Registration())->all();
        $this->render('admin/inscriptions', ['registrations' => $registrations], 'Gestion des inscriptions');
    }

    public function deleteRegistration(): void
    {
        require_admin();
        verify_csrf();
        $id = (int) ($_POST['registration_id'] ?? 0);
        if ($id > 0) {
            (new Registration())->delete($id);
            flash('success', 'Inscription supprimée.');
        }
        redirect('index.php?page=admin-inscriptions');
    }
}
