<?php
class PlantationController extends Controller
{
    public function mesPlantations()
    {
        $idUtilisateur = $this->utilisateurId();
        $this->render('FrontOffice', 'mes_plantations', [
            'plantations' => Plantation::all($idUtilisateur),
        ]);
    }

    public function ajouterPlantation()
    {
        $data = [
            'nom' => '',
            'region' => '',
            'surface' => '',
            'date' => '',
            'variete' => [],
            'variete_autre' => ''
        ];

        $erreurs = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data['nom'] = $this->postString('nom');
            $data['region'] = $this->postString('region');
            $data['surface'] = $this->postString('surface');
            $data['date'] = $this->postString('date');
            $data['variete_autre'] = $this->postString('variete_autre');
            $data['variete'] = $this->postStringList('variete');

            if (!$this->validateCsrfToken()) {
                $erreurs['_form'] = 'Le formulaire a expiré. Rechargez la page puis réessayez.';
            }
            if ($data['nom'] === '' || mb_strlen($data['nom']) > 100) {
                $erreurs['nom'] = 'Saisissez un nom de 1 à 100 caractères.';
            }
            if (!in_array($data['region'], Plantation::regions(), true)) {
                $erreurs['region'] = 'Choisissez une région valide.';
            }
            if (!is_numeric($data['surface']) || !is_finite((float) $data['surface']) || (float) $data['surface'] < 1 || (float) $data['surface'] > 1000000) {
                $erreurs['surface'] = 'La surface doit être comprise entre 1 et 1 000 000 m².';
            }
            if (!$this->dateValide($data['date']) || $data['date'] > date('Y-m-d')) {
                $erreurs['date'] = 'Choisissez une date valide, qui ne se situe pas dans le futur.';
            }
            $data['variete'] = array_values(array_unique(array_intersect($data['variete'], Plantation::varietes())));
            $varietesAEnregistrer = $data['variete'];
            if (in_array('Autre', $varietesAEnregistrer, true)) {
                $varietesAEnregistrer = array_diff($varietesAEnregistrer, ['Autre']);
                if ($data['variete_autre'] === '' || mb_strlen($data['variete_autre']) > 80) {
                    $erreurs['variete_autre'] = 'Précisez une variété de 1 à 80 caractères.';
                } else {
                    $varietesAEnregistrer[] = $data['variete_autre'];
                }
            }
            if (!$varietesAEnregistrer) {
                $erreurs['variete'] = 'Choisissez au moins une variété.';
            }

            if (!$erreurs) {
                Plantation::add([
                    'nom' => $data['nom'],
                    'region' => $data['region'],
                    'surface' => $data['surface'],
                    'date' => $data['date'],
                    'variete' => implode(', ', $varietesAEnregistrer),
                ], $this->utilisateurId());
                $this->setFlash('success', 'La plantation a bien été enregistrée.');
                $this->redirect('mes_plantations');
            }
        }

        $this->render('FrontOffice', 'ajouter_plantation', [
            'valeurs' => $data,
            'varietes' => Plantation::varietes(),
            'regions' => Plantation::regions(),
            'erreurs' => $erreurs,
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    public function ajouterSuivi()
    {
        $valeurs = [
            'plantation' => $this->queryString('plantation'),
            'date' => '',
            'etat' => '',
            'arrosage' => '',
            'observation' => '',
        ];
        $erreurs = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($valeurs as $cle => $valeur) {
                $valeurs[$cle] = $this->postString($cle);
            }
            if (!$this->validateCsrfToken()) {
                $erreurs['_form'] = 'Le formulaire a expiré. Rechargez la page puis réessayez.';
            }
            if ($valeurs['plantation'] === '' || !ctype_digit($valeurs['plantation']) || !Plantation::find((int) $valeurs['plantation'], $this->utilisateurId())) {
                $erreurs['plantation'] = 'Choisissez une plantation valide.';
            }
            if (!$this->dateValide($valeurs['date']) || $valeurs['date'] > date('Y-m-d')) {
                $erreurs['date'] = 'Choisissez une date valide, qui ne se situe pas dans le futur.';
            }
            if (!in_array($valeurs['etat'], Suivi::etats(), true)) {
                $erreurs['etat'] = 'Choisissez un état valide.';
            }
            if (!in_array($valeurs['arrosage'], Suivi::arrosages(), true)) {
                $erreurs['arrosage'] = 'Choisissez un niveau d’arrosage valide.';
            }
            if (mb_strlen($valeurs['observation']) > 1000) {
                $erreurs['observation'] = 'L’observation ne peut pas dépasser 1 000 caractères.';
            }
            if (!$erreurs) {
                Suivi::add($valeurs, $this->utilisateurId());
                $this->setFlash('success', 'Le suivi a bien été enregistré.');
                $this->redirect('historique', ['plantation' => (int) $valeurs['plantation']]);
            }
        }

        $this->render('FrontOffice', 'ajouter_suivi', [
            'plantations' => Plantation::all($this->utilisateurId()),
            'etats' => Suivi::etats(),
            'arrosages' => Suivi::arrosages(),
            'valeurs' => $valeurs,
            'erreurs' => $erreurs,
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    public function historique()
    {
        $idValue = $this->queryString('plantation');
        $id = ctype_digit($idValue) ? (int) $idValue : 0;
        $plantation = Plantation::find($id, $this->utilisateurId());

        $this->render('FrontOffice', 'historique', [
            'plantations' => Plantation::all($this->utilisateurId()),
            'plantation' => $plantation,
            'suivis' => $plantation ? Suivi::forPlantation($plantation->id, $this->utilisateurId()) : [],
            'selectionInvalide' => $idValue !== '' && !$plantation,
        ]);
    }

    public function recommandation()
    {
        $region = $this->queryString('region');
        $conseils = [
            'Béja' => ['Blé dur « Mahmoudi »'],
            'Gafsa' => ['Grenadier « Arbi »', 'Abricotier local'],
            'Nabeul' => ['Abricotier local'],
            'Sidi Bouzid' => ['Blé dur « Mahmoudi »'],
        ];
        $regionValide = $region === '' || in_array($region, Plantation::regions(), true);
        $resultat = $regionValide ? ($conseils[$region] ?? null) : null;

        $this->render('FrontOffice', 'recommandation', [
            'regions' => Plantation::regions(),
            'region' => $region,
            'resultat' => $resultat,
            'message' => !$regionValide
                ? 'Choisissez une région valide.'
                : ($region !== '' && !$resultat ? 'Aucune recommandation enregistrée pour cette région.' : ''),
        ]);
    }

    public function dashboard()
    {
        $this->render('BackOffice', 'dashboard', [
            'total' => count(Plantation::all()),
            'totalRegions' => count(array_unique(array_map(static function (Plantation $plantation): string {
                return $plantation->region;
            }, Plantation::all()))),
            'totalSuivis' => count(Suivi::all()),
        ]);
    }

    public function listePlantations()
    {
        $this->render('BackOffice', 'liste_plantations', [
            'plantations' => Plantation::all(),
        ]);
    }

    public function graines()
    {
        $this->render('BackOffice', 'graines', ['varietes' => Plantation::varietes()]);
    }

    public function statistiques()
    {
        $plantations = Plantation::all();
        $regions = [];
        foreach ($plantations as $plantation) {
            $regions[$plantation->region] = ($regions[$plantation->region] ?? 0) + 1;
        }
        arsort($regions);

        $this->render('BackOffice', 'statistiques', [
            'plantations' => $plantations,
            'totalSuivis' => count(Suivi::all()),
            'regionActive' => $regions ? array_key_first($regions) : 'Aucune',
            'regions' => $regions,
        ]);
    }

    public function notFound(): void
    {
        http_response_code(404);
        $this->render('FrontOffice', 'not_found');
    }

    private function utilisateurId(): int
    {
        $utilisateur = $_SESSION['utilisateur'] ?? null;
        if (!is_array($utilisateur) || !isset($utilisateur['idUtilisateur'])) {
            http_response_code(403);
            exit;
        }

        return (int) $utilisateur['idUtilisateur'];
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function postStringList(string $key): array
    {
        $values = $_POST[$key] ?? [];
        if (!is_array($values)) {
            return [];
        }

        $strings = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $strings[] = trim($value);
            }
        }

        return $strings;
    }

    private function queryString(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function dateValide(string $date): bool
    {
        $dateObj = DateTime::createFromFormat('!Y-m-d', $date);

        return $dateObj !== false
            && $dateObj->format('Y-m-d') === $date
            && DateTime::getLastErrors() === false;
    }
}
