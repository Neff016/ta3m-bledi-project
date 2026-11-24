<?php
require_once __DIR__ . '/AdminController.php';

// CRUD of Utilisateur + roles + block/unblock (admin only)
class UsersController extends AdminController
{
    public function index(): void
    {
        $this->requireLogin();
        $users = Utilisateur::all();
        usort($users, fn($a, $b) => $a['idUtilisateur'] <=> $b['idUtilisateur']);
        $this->renderAdmin('users', 'users', 'Utilisateurs', ['users' => $users, 'me' => (int)$this->user['idUtilisateur']]);
    }

    public function form(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $u  = $id ? Utilisateur::findById($id) : null;
        if ($id && !$u) {
            $this->flash('Utilisateur introuvable.', 'danger');
            $this->redirect('users');
        }
        [$errors, $old] = $this->takeFormState();
        $this->renderAdmin('user_form', 'users', $id ? 'Modifier un utilisateur' : 'Nouvel utilisateur', [
            'u' => array_merge($u ?? [], $old), 'errors' => $errors, 'isMe' => $id === (int)$this->user['idUtilisateur'],
        ]);
    }

    public function save(): void
    {
        $this->requireLogin();
        $this->requirePost('users');

        $id = (int)($_POST['idUtilisateur'] ?? 0);
        $existing = $id ? Utilisateur::findById($id) : null;
        if ($id && !$existing) {
            $this->flash('Utilisateur introuvable.', 'danger');
            $this->redirect('users');
        }

        $in = [
            'nom'        => trim((string)($_POST['nom'] ?? '')),
            'prenom'     => trim((string)($_POST['prenom'] ?? '')),
            'email'      => strtolower(trim((string)($_POST['email'] ?? ''))),
            'telephone'  => trim((string)($_POST['telephone'] ?? '')),
            'role'       => (string)($_POST['role'] ?? ''),
            'statut'     => (string)($_POST['statut'] ?? ''),
            'motDePasse' => (string)($_POST['motDePasse'] ?? ''),
        ];

        $errors = Utilisateur::validate($in, $id);

        // Business rules on admin accounts
        if ($existing && $existing['role'] === 'admin' && $existing['statut'] === 'actif'
            && ($in['role'] !== 'admin' || $in['statut'] !== 'actif')) {
            if ($id === (int)$this->user['idUtilisateur']) {
                $errors[] = 'Vous ne pouvez pas retirer vos propres droits ni bloquer votre compte.';
            } elseif (Utilisateur::countActiveAdmins($id) === 0) {
                $errors[] = 'Il doit rester au moins un administrateur actif.';
            }
        }

        if ($errors) {
            unset($in['motDePasse']);
            $this->back('user-form', $id, $errors, $in);
        }

        $row = [
            'idUtilisateur'   => $id ?: Utilisateur::nextId(),
            'nom'             => $in['nom'],
            'prenom'          => $in['prenom'],
            'email'           => $in['email'],
            'motDePasse'      => $existing['motDePasse'] ?? '',
            'telephone'       => $in['telephone'],
            'role'            => $in['role'],
            'statut'          => $in['statut'],
            'dateInscription' => $existing['dateInscription'] ?? date('Y-m-d'),
        ];
        if ($in['motDePasse'] !== '') {
            $row['motDePasse'] = password_hash($in['motDePasse'], PASSWORD_DEFAULT);
        }
        Utilisateur::save($row);
        $this->flash('Utilisateur enregistré.');
        $this->redirect('users');
    }

    public function toggle(): void
    {
        $this->requireLogin();
        $this->requirePost('users');
        $id = (int)($_POST['id'] ?? 0);
        $u  = Utilisateur::findById($id);
        if (!$u) {
            $this->flash('Utilisateur introuvable.', 'danger');
            $this->redirect('users');
        }
        if ($u['statut'] === 'actif' && $u['role'] === 'admin') {
            if ($id === (int)$this->user['idUtilisateur']) {
                $this->flash('Vous ne pouvez pas bloquer votre propre compte.', 'danger');
                $this->redirect('users');
            }
            if (Utilisateur::countActiveAdmins($id) === 0) {
                $this->flash('Il doit rester au moins un administrateur actif.', 'danger');
                $this->redirect('users');
            }
        }
        $u['statut'] = $u['statut'] === 'actif' ? 'bloqué' : 'actif';
        Utilisateur::save($u);
        $this->flash($u['statut'] === 'actif' ? 'Compte débloqué.' : 'Compte bloqué.');
        $this->redirect('users');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requirePost('users');
        $id = (int)($_POST['id'] ?? 0);
        $u  = Utilisateur::findById($id);
        if (!$u) {
            $this->flash('Utilisateur introuvable.', 'danger');
            $this->redirect('users');
        }
        if ($id === (int)$this->user['idUtilisateur']) {
            $this->flash('Vous ne pouvez pas supprimer votre propre compte.', 'danger');
            $this->redirect('users');
        }
        if ($u['role'] === 'admin' && $u['statut'] === 'actif' && Utilisateur::countActiveAdmins($id) === 0) {
            $this->flash('Il doit rester au moins un administrateur actif.', 'danger');
            $this->redirect('users');
        }
        Utilisateur::delete($id);
        $this->flash('Utilisateur supprimé.');
        $this->redirect('users');
    }
}
