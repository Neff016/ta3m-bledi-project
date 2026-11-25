<?php
/*
 * Back Office : supprimer une culture du catalogue (les récoltes déjà saisies ne changent pas)
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/CultureController.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id && (new CultureController())->deleteCulture($id)) {
        setFlash('Culture supprimée du catalogue.');
    } else {
        setFlash('Impossible de supprimer cette culture.', 'danger');
    }
}
header('Location: listeCultures.php');
exit;
