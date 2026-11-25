<?php
/*
 * Appels HTTP vers les API externes (Open-Meteo, Agromonitoring)
 * avec cURL (activé par défaut dans XAMPP).
 *
 * httpJson(...) retourne ['ok' => true, 'donnees' => tableau] ou ['ok' => false, 'erreur' => '...']
 */

// Clés d'API facultatives (fichier secret, jamais sur GitHub) : voir Config/api.example.php
if (is_file(__DIR__ . '/../Config/api.php')) {
    require_once __DIR__ . '/../Config/api.php';
}

function cleApi(string $nom): string
{
    return defined($nom) ? trim((string) constant($nom)) : '';
}

function httpJson(string $url, string $methode = 'GET', ?array $corps = null, int $delai = 15): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'erreur' => "L'extension PHP cURL n'est pas activée (php.ini : extension=curl)."];
    }
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $delai,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_CUSTOMREQUEST  => $methode,
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/json'],
        CURLOPT_USERAGENT      => 'TaamBledi/1.0 (projet etudiant)',
    ];
    // Certificats SSL fournis par XAMPP (sinon cURL ne peut pas vérifier HTTPS sous Windows)
    foreach (['C:\\xampp\\apache\\bin\\curl-ca-bundle.crt', 'C:\\xampp\\php\\extras\\ssl\\cacert.pem'] as $ca) {
        if (is_file($ca)) {
            $options[CURLOPT_CAINFO] = $ca;
            break;
        }
    }
    if ($corps !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($corps);
    }
    curl_setopt_array($ch, $options);

    $reponse = curl_exec($ch);
    $code    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreur  = curl_error($ch);
    curl_close($ch);

    if ($reponse === false) {
        return ['ok' => false, 'erreur' => "Service injoignable ($erreur). Vérifiez la connexion Internet."];
    }
    $donnees = json_decode($reponse, true);
    if ($code < 200 || $code >= 300) {
        $message = is_array($donnees) ? ($donnees['error']['message'] ?? $donnees['message'] ?? $donnees['reason'] ?? '') : '';
        return ['ok' => false, 'code' => $code, 'erreur' => "Le service a répondu avec l'erreur $code" . ($message !== '' ? " : $message" : '.')];
    }
    if (!is_array($donnees)) {
        return ['ok' => false, 'erreur' => 'Réponse du service illisible.'];
    }
    return ['ok' => true, 'donnees' => $donnees];
}

// Petit cache fichier (évite d'appeler l'API à chaque affichage)
function cacheLire(string $cle, int $dureeSecondes): ?array
{
    $fichier = sys_get_temp_dir() . '/taambledi_' . md5($cle) . '.json';
    if (is_file($fichier) && filemtime($fichier) > time() - $dureeSecondes) {
        $donnees = json_decode((string) file_get_contents($fichier), true);
        return is_array($donnees) ? $donnees : null;
    }
    return null;
}

function cacheEcrire(string $cle, array $donnees): void
{
    @file_put_contents(sys_get_temp_dir() . '/taambledi_' . md5($cle) . '.json', json_encode($donnees));
}
