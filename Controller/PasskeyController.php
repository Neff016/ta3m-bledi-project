<?php
/*
 * Clés d'accès (passkeys / WebAuthn) : se connecter SANS mot de passe
 * avec Face ID, l'empreinte digitale, Windows Hello ou le code PIN de l'appareil.
 *
 * Principe :
 *  - Inscription : l'appareil crée une paire de clés. On garde seulement la clé PUBLIQUE.
 *  - Connexion   : le serveur envoie un défi (challenge) aléatoire, l'appareil le signe
 *                  avec la clé PRIVÉE (qui ne quitte jamais l'appareil), on vérifie la
 *                  signature avec la clé publique enregistrée.
 *
 * Sans bibliothèque externe : décodage CBOR + conversion de la clé COSE en PEM + openssl_verify.
 */
require_once __DIR__ . '/../Config/config.php';

class PasskeyController
{
    public const NOM_SITE = 'Taam Bledi';

    /* =========================================================
     *  BASE DE DONNÉES
     * ========================================================= */

    public function listePasskeys(int $idUtilisateur): array
    {
        $req = config::getConnexion()->prepare(
            "SELECT id, nom, dateCreation, derniereUtilisation FROM passkey
             WHERE idUtilisateur = :u ORDER BY dateCreation DESC"
        );
        $req->execute(['u' => $idUtilisateur]);
        return $req->fetchAll();
    }

    public function supprimerPasskey(int $id, int $idUtilisateur): bool
    {
        $req = config::getConnexion()->prepare("DELETE FROM passkey WHERE id = :id AND idUtilisateur = :u");
        $req->execute(['id' => $id, 'u' => $idUtilisateur]);
        return $req->rowCount() > 0;
    }

    private function getParIdentifiant(string $credentialId)
    {
        $req = config::getConnexion()->prepare("SELECT * FROM passkey WHERE credentialId = :c");
        $req->execute(['c' => $credentialId]);
        return $req->fetch();
    }

    private function identifiantsUtilisateur(int $idUtilisateur): array
    {
        $req = config::getConnexion()->prepare("SELECT credentialId FROM passkey WHERE idUtilisateur = :u");
        $req->execute(['u' => $idUtilisateur]);
        return $req->fetchAll(PDO::FETCH_COLUMN);
    }

    /* =========================================================
     *  1) INSCRIPTION D'UNE CLÉ (utilisateur connecté, page profil)
     * ========================================================= */

    // Options envoyées au navigateur pour navigator.credentials.create()
    public function optionsInscription(array $user): array
    {
        $defi = random_bytes(32);
        $_SESSION['passkey_defi'] = self::b64url($defi);

        return [
            'challenge' => self::b64url($defi),
            'rp'        => ['name' => self::NOM_SITE, 'id' => self::rpId()],
            'user'      => [
                'id'          => self::b64url(pack('N', (int) $user['id'])),
                'name'        => $user['email'],
                'displayName' => $user['prenom'] . ' ' . $user['nom'],
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],     // ES256 (le plus courant)
                ['type' => 'public-key', 'alg' => -257],   // RS256 (Windows Hello)
            ],
            'authenticatorSelection' => [
                'residentKey'      => 'required',   // la clé se retrouve sans taper l'email
                'userVerification' => 'required',   // Face ID / empreinte / PIN obligatoire
            ],
            'excludeCredentials' => array_map(
                fn($id) => ['type' => 'public-key', 'id' => $id],
                $this->identifiantsUtilisateur((int) $user['id'])
            ),
            'attestation' => 'none',
            'timeout'     => 60000,
        ];
    }

    // Vérifie la réponse du navigateur et enregistre la clé publique
    public function enregistrer(int $idUtilisateur, array $donnees, string $nom): array
    {
        try {
            $clientJson = self::b64urlDecode($donnees['clientDataJSON'] ?? '');
            $this->verifierClientData($clientJson, 'webauthn.create');

            $attestation = self::cbor(self::b64urlDecode($donnees['attestationObject'] ?? ''));
            $authData    = $attestation['authData'] ?? '';
            $infos       = $this->lireAuthData($authData, true);

            if ($this->getParIdentifiant($infos['credentialId'])) {
                throw new RuntimeException('Cette clé est déjà enregistrée.');
            }

            $req = config::getConnexion()->prepare(
                "INSERT INTO passkey (idUtilisateur, credentialId, clePublique, compteur, nom)
                 VALUES (:u, :c, :pk, :n, :nom)"
            );
            $req->execute([
                'u'   => $idUtilisateur,
                'c'   => $infos['credentialId'],
                'pk'  => $infos['clePem'],
                'n'   => $infos['compteur'],
                'nom' => $nom,
            ]);
            return ['ok' => true];
        } catch (Throwable $ex) {
            return ['ok' => false, 'erreur' => $ex->getMessage()];
        }
    }

    /* =========================================================
     *  2) CONNEXION AVEC UNE CLÉ (page connexion)
     * ========================================================= */

    public function optionsConnexion(): array
    {
        $defi = random_bytes(32);
        $_SESSION['passkey_defi'] = self::b64url($defi);
        return [
            'challenge'        => self::b64url($defi),
            'rpId'             => self::rpId(),
            'userVerification' => 'required',
            'timeout'          => 60000,
        ];
    }

    // Retourne ['ok' => true, 'idUtilisateur' => ...] ou ['ok' => false, 'erreur' => ...]
    public function verifierConnexion(array $donnees): array
    {
        try {
            $cle = $this->getParIdentifiant((string) ($donnees['id'] ?? ''));
            if (!$cle) {
                throw new RuntimeException("Cette clé d'accès n'est liée à aucun compte.");
            }

            $clientJson = self::b64urlDecode($donnees['clientDataJSON'] ?? '');
            $this->verifierClientData($clientJson, 'webauthn.get');

            $authData = self::b64urlDecode($donnees['authenticatorData'] ?? '');
            $infos    = $this->lireAuthData($authData, false);

            // La signature porte sur : authenticatorData + SHA-256(clientDataJSON)
            $signature = self::b64urlDecode($donnees['signature'] ?? '');
            $valide    = openssl_verify($authData . hash('sha256', $clientJson, true), $signature,
                                        $cle['clePublique'], OPENSSL_ALGO_SHA256);
            if ($valide !== 1) {
                throw new RuntimeException('Signature invalide.');
            }

            // Compteur : s'il recule, la clé a peut-être été copiée
            if ($infos['compteur'] > 0 && $infos['compteur'] <= (int) $cle['compteur']) {
                throw new RuntimeException("Compteur de la clé incohérent : connexion refusée.");
            }
            $req = config::getConnexion()->prepare(
                "UPDATE passkey SET compteur = :n, derniereUtilisation = NOW() WHERE id = :id"
            );
            $req->execute(['n' => $infos['compteur'], 'id' => $cle['id']]);

            return ['ok' => true, 'idUtilisateur' => (int) $cle['idUtilisateur']];
        } catch (Throwable $ex) {
            return ['ok' => false, 'erreur' => $ex->getMessage()];
        }
    }

    /* =========================================================
     *  VÉRIFICATIONS COMMUNES
     * ========================================================= */

    private function verifierClientData(string $json, string $typeAttendu): void
    {
        $client = json_decode($json, true);
        $defi   = $_SESSION['passkey_defi'] ?? null;
        unset($_SESSION['passkey_defi']);                       // un défi ne sert qu'une fois

        if (!is_array($client) || ($client['type'] ?? '') !== $typeAttendu) {
            throw new RuntimeException('Réponse de l’appareil invalide.');
        }
        if (!$defi || !hash_equals($defi, (string) ($client['challenge'] ?? ''))) {
            throw new RuntimeException('Défi expiré ou incorrect, recommencez.');
        }
        if (($client['origin'] ?? '') !== self::origine()) {
            throw new RuntimeException('Origine du site incorrecte.');
        }
    }

    // authenticatorData : hash du site (32) | flags (1) | compteur (4) | [données de la clé]
    private function lireAuthData(string $authData, bool $avecCle): array
    {
        if (strlen($authData) < 37) {
            throw new RuntimeException('Données de l’appareil incomplètes.');
        }
        if (!hash_equals(hash('sha256', self::rpId(), true), substr($authData, 0, 32))) {
            throw new RuntimeException('Cette clé appartient à un autre site.');
        }
        $flags = ord($authData[32]);
        if (!($flags & 0x01) || !($flags & 0x04)) {   // UP (présence) + UV (Face ID / empreinte / PIN)
            throw new RuntimeException('Vérification de l’utilisateur (Face ID, empreinte ou PIN) requise.');
        }
        $infos = ['compteur' => unpack('N', substr($authData, 33, 4))[1]];

        if ($avecCle) {
            if (!($flags & 0x40)) {
                throw new RuntimeException('Aucune clé dans la réponse.');
            }
            $longueur = unpack('n', substr($authData, 53, 2))[1];
            $infos['credentialId'] = self::b64url(substr($authData, 55, $longueur));
            $pos  = 55 + $longueur;
            $cose = self::cbor($authData, $pos);
            $infos['clePem'] = self::coseVersPem($cose);
        }
        return $infos;
    }

    /* =========================================================
     *  OUTILS : site, base64url, CBOR, COSE → PEM
     * ========================================================= */

    public static function rpId(): string
    {
        return preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public static function origine(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public static function b64url(string $binaire): string
    {
        return rtrim(strtr(base64_encode($binaire), '+/', '-_'), '=');
    }

    public static function b64urlDecode(string $texte): string
    {
        $resultat = base64_decode(strtr($texte, '-_', '+/'), true);
        if ($resultat === false) {
            throw new RuntimeException('Donnée mal encodée.');
        }
        return $resultat;
    }

    // Décodeur CBOR minimal (format binaire utilisé par WebAuthn)
    public static function cbor(string $data, int &$pos = 0)
    {
        if ($pos >= strlen($data)) {
            throw new RuntimeException('CBOR tronqué.');
        }
        $octet  = ord($data[$pos++]);
        $type   = $octet >> 5;
        $info   = $octet & 0x1f;
        $valeur = $info;
        if ($info === 24) {
            $valeur = ord($data[$pos]); $pos += 1;
        } elseif ($info === 25) {
            $valeur = unpack('n', substr($data, $pos, 2))[1]; $pos += 2;
        } elseif ($info === 26) {
            $valeur = unpack('N', substr($data, $pos, 4))[1]; $pos += 4;
        } elseif ($info === 27) {
            $valeur = unpack('J', substr($data, $pos, 8))[1]; $pos += 8;
        } elseif ($info > 27) {
            throw new RuntimeException('CBOR non supporté.');
        }

        switch ($type) {
            case 0: return $valeur;                                    // entier positif
            case 1: return -1 - $valeur;                               // entier négatif
            case 2:                                                    // octets
            case 3:                                                    // texte
                $s = substr($data, $pos, $valeur); $pos += $valeur; return $s;
            case 4:                                                    // tableau
                $t = [];
                for ($i = 0; $i < $valeur; $i++) { $t[] = self::cbor($data, $pos); }
                return $t;
            case 5:                                                    // dictionnaire
                $m = [];
                for ($i = 0; $i < $valeur; $i++) { $k = self::cbor($data, $pos); $m[$k] = self::cbor($data, $pos); }
                return $m;
            case 7:                                                    // false / true / null
                return [20 => false, 21 => true, 22 => null][$info] ?? null;
        }
        throw new RuntimeException('CBOR non supporté.');
    }

    // Clé COSE (format WebAuthn) → clé publique PEM (format openssl)
    private static function coseVersPem(array $cose): string
    {
        $kty = $cose[1] ?? null;
        $alg = $cose[3] ?? null;

        if ($kty === 2 && $alg === -7) {                              // EC P-256 (ES256)
            $x = $cose[-2] ?? ''; $y = $cose[-3] ?? '';
            if (($cose[-1] ?? 0) !== 1 || strlen($x) !== 32 || strlen($y) !== 32) {
                throw new RuntimeException('Clé EC invalide.');
            }
            $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . "\x04" . $x . $y;
        } elseif ($kty === 3 && $alg === -257) {                      // RSA (RS256)
            $n = $cose[-1] ?? ''; $e = $cose[-2] ?? '';
            $cleRsa = self::der(0x30, self::der(0x02, self::entierDer($n)) . self::der(0x02, self::entierDer($e)));
            $algo   = self::der(0x30, self::der(0x06, hex2bin('2a864886f70d010101')) . "\x05\x00");
            $der    = self::der(0x30, $algo . self::der(0x03, "\x00" . $cleRsa));
        } else {
            throw new RuntimeException('Type de clé non supporté.');
        }
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function der(int $tag, string $contenu): string
    {
        $l = strlen($contenu);
        if ($l < 128) {
            $longueur = chr($l);
        } else {
            $octets   = ltrim(pack('N', $l), "\x00");
            $longueur = chr(0x80 | strlen($octets)) . $octets;
        }
        return chr($tag) . $longueur . $contenu;
    }

    private static function entierDer(string $octets): string
    {
        $octets = ltrim($octets, "\x00");
        return (ord($octets[0] ?? "\x00") & 0x80) ? "\x00" . $octets : $octets;
    }
}
