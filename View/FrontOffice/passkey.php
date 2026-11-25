<?php
/*
 * Clés d'accès (passkeys) : petites requêtes envoyées par assets/js/passkey.js
 * Réponses en JSON. Toutes les requêtes sont en POST avec le jeton CSRF.
 *
 *  action=options-inscription  (connecté)  → options pour créer une clé
 *  action=inscription          (connecté)  → enregistre la clé
 *  action=options-connexion    (public)    → défi à signer
 *  action=connexion            (public)    → vérifie la signature et ouvre la session
 *  action=supprimer            (connecté)  → formulaire classique du profil
 */
require_once __DIR__ . '/../../Controller/AuthGuard.php';
require_once __DIR__ . '/../../Controller/UtilisateurController.php';
require_once __DIR__ . '/../../Controller/PasskeyController.php';

function repondre(array $donnees, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($donnees);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    repondre(['ok' => false, 'erreur' => 'Méthode non autorisée.'], 405);
}

$action       = $_POST['action'] ?? '';
$passkeyC     = new PasskeyController();
$utilisateurC = new UtilisateurController();
$donnees      = json_decode($_POST['donnees'] ?? '{}', true) ?: [];

switch ($action) {
    case 'options-inscription':
        $user = $utilisateurC->getUtilisateurActif(requireLogin());
        repondre(['ok' => true, 'options' => $passkeyC->optionsInscription($user)]);

    case 'inscription':
        $id  = requireLogin();
        $nom = trim($_POST['nom'] ?? '');
        if ($nom === '' || mb_strlen($nom) > 50) {
            repondre(['ok' => false, 'erreur' => 'Donnez un nom à la clé (50 caractères maximum).'], 422);
        }
        $resultat = $passkeyC->enregistrer($id, $donnees, $nom);
        if ($resultat['ok']) {
            setFlash("Clé d'accès « $nom » ajoutée. Vous pouvez maintenant vous connecter sans mot de passe.");
        }
        repondre($resultat, $resultat['ok'] ? 200 : 422);

    case 'options-connexion':
        repondre(['ok' => true, 'options' => $passkeyC->optionsConnexion()]);

    case 'connexion':
        $resultat = $passkeyC->verifierConnexion($donnees);
        if (!$resultat['ok']) {
            repondre($resultat, 401);
        }
        $user = $utilisateurC->getUtilisateur($resultat['idUtilisateur']);
        if (!$user || $user['statut'] === 'SUPPRIME') {
            repondre(['ok' => false, 'erreur' => 'Ce compte a été supprimé.'], 403);
        }
        if ($user['statut'] === 'BLOQUE') {
            repondre(['ok' => false, 'erreur' => "Votre compte est bloqué. Contactez l'administrateur."], 403);
        }
        repondre(['ok' => true, 'redirection' => demarrerSession($utilisateurC, $user)]);

    case 'supprimer':
        $id = requireLogin();
        $idCle = filter_input(INPUT_POST, 'idCle', FILTER_VALIDATE_INT);
        if ($idCle && $passkeyC->supprimerPasskey($idCle, $id)) {
            setFlash("Clé d'accès supprimée.");
        } else {
            setFlash("Cette clé d'accès n'existe pas.", 'danger');
        }
        header('Location: profil.php?onglet=securite');
        exit;
}

repondre(['ok' => false, 'erreur' => 'Action inconnue.'], 400);
