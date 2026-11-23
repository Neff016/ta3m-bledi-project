<?php
declare(strict_types=1);

class RegistrationController extends Controller
{
    public function mine(): void
    {
        require_user();
        $items = (new Registration())->forUser((int) current_user()['id']);
        $this->render('mes-inscriptions', ['registrations' => $items], 'Mes inscriptions');
    }

    public function store(): void
    {
        require_user();
        verify_csrf();
        $workshopId = filter_input(INPUT_POST, 'workshop_id', FILTER_VALIDATE_INT);
        if (!$workshopId) {
            flash('error', 'Atelier invalide.');
            redirect('index.php?page=ateliers');
        }
        try {
            (new Registration())->register((int) current_user()['id'], $workshopId);
            flash('success', 'Ton inscription a été confirmée.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('index.php?page=mes-inscriptions');
    }

    public function cancel(): void
    {
        require_user();
        verify_csrf();
        $id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
        if ($id) {
            (new Registration())->cancelForUser($id, (int) current_user()['id']);
            flash('success', 'Inscription annulée.');
        }
        redirect('index.php?page=mes-inscriptions');
    }
}
