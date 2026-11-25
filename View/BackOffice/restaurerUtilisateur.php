<?php
/*
 * Action : restaurer un compte supprimé (Back Office, admin)
 * Le compte redevient actif avec ses terrains, récoltes et badges.
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/UtilisateurController.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $utilisateurC = new UtilisateurController();
    $user         = $id ? $utilisateurC->getUtilisateurSupprime($id) : false;

    if (!$user) {
        setFlash("Ce compte n'est pas dans la corbeille.", 'danger');
    } else {
        $utilisateurC->restaurerUtilisateur($id);
        setFlash('Le compte de ' . $user['prenom'] . ' ' . $user['nom'] . ' a été restauré. Il peut de nouveau se connecter.');
    }
    unset($_SESSION['annuler_suppression']);
}

header('Location: listeUtilisateurs.php');
exit;
