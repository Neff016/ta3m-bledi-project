<?php
/*
 * Action : supprimer un utilisateur (Back Office, admin)
 * Le compte va dans la corbeille : il peut être restauré avec le bouton « Restaurer ».
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/UtilisateurController.php';

$idAdmin = requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $utilisateurC = new UtilisateurController();
    $user         = $id ? $utilisateurC->getUtilisateurActif($id) : false;

    if (!$user) {
        setFlash("Cet utilisateur n'existe pas.", 'danger');
    } elseif ($id === $idAdmin) {
        setFlash('Vous ne pouvez pas supprimer votre propre compte.', 'danger');
    } else {
        $utilisateurC->deleteUtilisateur($id);
        // Pour afficher le bouton « Annuler » juste après la suppression
        $_SESSION['annuler_suppression'] = ['id' => $id, 'nom' => $user['prenom'] . ' ' . $user['nom']];
    }
}

header('Location: listeUtilisateurs.php');
exit;
