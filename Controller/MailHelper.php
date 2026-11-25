<?php
/*
 * Envoi d'emails par SMTP (Gmail) sans bibliothèque externe.
 * Les identifiants sont dans Config/mail.php (voir Config/mail.example.php).
 *
 * envoyerEmail(...) retourne ['ok' => true] ou ['ok' => false, 'erreur' => '...']
 */

function envoyerEmail(string $destinataire, string $nomDestinataire, string $sujet, string $html, string $texte): array
{
    $config = __DIR__ . '/../Config/mail.php';
    if (!is_file($config)) {
        return ['ok' => false, 'erreur' => "L'envoi d'emails n'est pas configuré (fichier Config/mail.php manquant)."];
    }
    require_once $config;

    // Sécurité : pas de retour à la ligne dans les en-têtes (injection d'en-têtes)
    if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $nomDestinataire . $sujet)) {
        return ['ok' => false, 'erreur' => 'Adresse email invalide.'];
    }

    // Certificats SSL : XAMPP fournit un fichier de certificats, on l'utilise s'il existe
    $ssl = ['verify_peer' => true, 'verify_peer_name' => true];
    foreach (['C:\\xampp\\apache\\bin\\curl-ca-bundle.crt', 'C:\\xampp\\php\\extras\\ssl\\cacert.pem'] as $ca) {
        if (is_file($ca)) {
            $ssl['cafile'] = $ca;
            break;
        }
    }

    $smtp = @stream_socket_client(
        'ssl://' . MAIL_HOTE . ':' . MAIL_PORT, $errno, $errstr, 15,
        STREAM_CLIENT_CONNECT, stream_context_create(['ssl' => $ssl])
    );
    if (!$smtp) {
        return ['ok' => false, 'erreur' => "Connexion au serveur mail impossible ($errstr). Vérifiez Internet et l'extension openssl."];
    }
    stream_set_timeout($smtp, 15);

    // Lit la réponse du serveur (peut faire plusieurs lignes) et vérifie le code attendu
    $lire = function (string $attendu) use ($smtp): bool {
        $reponse = '';
        while (($ligne = fgets($smtp, 515)) !== false) {
            $reponse .= $ligne;
            if (isset($ligne[3]) && $ligne[3] === ' ') {
                break;
            }
        }
        return strncmp($reponse, $attendu, 3) === 0;
    };
    $envoyer = function (string $commande, string $attendu) use ($smtp, $lire): bool {
        fwrite($smtp, $commande . "\r\n");
        return $lire($attendu);
    };

    $encoder = fn(string $t) => '=?UTF-8?B?' . base64_encode($t) . '?=';
    $frontiere = 'tb_' . bin2hex(random_bytes(8));
    $message = implode("\r\n", [
        'Date: ' . date('r'),
        'From: ' . $encoder(MAIL_NOM_EXPEDITEUR) . ' <' . MAIL_UTILISATEUR . '>',
        'To: ' . $encoder($nomDestinataire) . ' <' . $destinataire . '>',
        'Subject: ' . $encoder($sujet),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@taambledi.local>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $frontiere . '"',
        '',
        '--' . $frontiere,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        rtrim(chunk_split(base64_encode($texte))),
        '--' . $frontiere,
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        rtrim(chunk_split(base64_encode($html))),
        '--' . $frontiere . '--',
    ]);

    $etapes = [
        [null, '220', 'Le serveur mail ne répond pas.'],
        ['EHLO localhost', '250', 'Le serveur mail a refusé la connexion.'],
        ['AUTH LOGIN', '334', "Le serveur mail refuse l'authentification."],
        [base64_encode(MAIL_UTILISATEUR), '334', 'Adresse Gmail refusée.'],
        [base64_encode(str_replace(' ', '', MAIL_MOT_DE_PASSE)), '235',
            "Gmail a refusé l'identifiant ou le mot de passe d'application (Config/mail.php)."],
        ['MAIL FROM:<' . MAIL_UTILISATEUR . '>', '250', 'Expéditeur refusé.'],
        ['RCPT TO:<' . $destinataire . '>', '250', 'Destinataire refusé.'],
        ['DATA', '354', 'Le serveur refuse le message.'],
        [$message . "\r\n.", '250', "Le message n'a pas été accepté."],
    ];
    foreach ($etapes as [$commande, $attendu, $erreur]) {
        $ok = $commande === null ? $lire($attendu) : $envoyer($commande, $attendu);
        if (!$ok) {
            fclose($smtp);
            return ['ok' => false, 'erreur' => $erreur];
        }
    }
    $envoyer('QUIT', '221');
    fclose($smtp);
    return ['ok' => true];
}

// Email du code « mot de passe oublié »
function envoyerCodeReinitialisation(string $email, string $prenom, string $code, int $minutes): array
{
    $p = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
    $html = <<<HTML
<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;border:1px solid #e3e8e0;border-radius:12px;overflow:hidden">
  <div style="background:#2f5d3a;color:#fff;padding:18px 24px;font-size:20px;font-weight:bold">Taam Bledi</div>
  <div style="padding:24px;color:#333">
    <p>Bonjour {$p},</p>
    <p>Vous avez demandé à réinitialiser votre mot de passe. Voici votre code :</p>
    <p style="font-size:32px;letter-spacing:8px;font-weight:bold;color:#2f5d3a;text-align:center;margin:24px 0">{$code}</p>
    <p>Ce code est valable <strong>{$minutes} minutes</strong>.</p>
    <p style="color:#888;font-size:13px">Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : votre mot de passe ne change pas.</p>
  </div>
</div>
HTML;
    $texte = "Bonjour $prenom,\n\nVotre code Taam Bledi : $code\nIl est valable $minutes minutes.\n\n"
           . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.";
    return envoyerEmail($email, $prenom, 'Votre code Taam Bledi : ' . $code, $html, $texte);
}

// Crée un nouveau code et l'envoie par email.
// Si l'email ne correspond à aucun compte actif, rien n'est envoyé mais on répond « ok »
// (on ne révèle pas quels emails existent).
function demanderCodeParEmail(UtilisateurController $utilisateurC, string $email): array
{
    $user = $utilisateurC->getByEmail($email);
    $code = $utilisateurC->creerCodeReinitialisation($email);
    if ($code === null) {
        return ['ok' => true];
    }
    $_SESSION['reset_dernier_envoi'] = time();
    return envoyerCodeReinitialisation($email, $user['prenom'], $code, UtilisateurController::CODE_DUREE_MINUTES);
}
