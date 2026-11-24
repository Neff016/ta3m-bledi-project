<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Model/Ville.php';
require_once __DIR__ . '/../Model/Projection.php';
require_once __DIR__ . '/../Model/ExplicationIA.php';

class VilleController extends Controller
{
    // Use case: choisir une région
    public function index(): void
    {
        $cards = [];
        foreach (Ville::findAll() as $v) {
            $ps = Projection::findByVille($v->idVille);
            $cards[] = [
                'ville' => $v,
                'base'  => $ps[0] ?? null,
                'cible' => $ps ? $ps[count($ps) - 1] : null,
            ];
        }

        $this->render('FrontOffice', 'villes', [
            'pageTitle'   => 'Tunisie2050 – Choisir une région',
            'currentPage' => 'tounes',
            'useMap'      => true,
            'cards'       => $cards,
        ]);
    }

    // Use case: naviguer dans le temps
    public function show(int $id): void
    {
        $ville = Ville::findById($id);
        if (!$ville) {
            $this->notFound('Région introuvable');
            return;
        }

        $timeline = array_map(fn(Projection $p) => [
            'annee'          => $p->annee,
            'temperatureMoy' => $p->temperatureMoy,
            'pluviometrie'   => $p->pluviometrie,
            'stressHydrique' => $p->stressHydrique,
            'stressClass'    => $p->stressClass(),
            'cultures'       => $p->cultures(),
            'description'    => $p->description,
        ], Projection::findByVille($id));

        $this->render('FrontOffice', 'ville', [
            'pageTitle'   => 'Tunisie2050 – ' . $ville->nom,
            'currentPage' => 'tounes',
            'useMap'      => true,
            'useChart'    => true,
            'ville'       => $ville,
            'timeline'    => $timeline,
            'annee'       => (int)($_GET['annee'] ?? 0),
        ]);
    }

    // Use case: voir les cultures menacées
    public function cultures(): void
    {
        $years = Projection::years();
        $annee = (int)($_GET['annee'] ?? 0);
        if (!in_array($annee, $years, true)) {
            $annee = $years ? $years[count($years) - 1] : 0;
        }

        $rows = [];
        $ranking = [];
        foreach (Projection::findByYear($annee) as $p) {
            $v = Ville::findById($p->idVille);
            $c = $p->cultures();
            if (!$v || !$c) {
                continue;
            }
            $rows[] = ['ville' => $v, 'projection' => $p, 'cultures' => $c];
            foreach ($c as $name) {
                $ranking[$name] = ($ranking[$name] ?? 0) + 1;
            }
        }
        arsort($ranking);

        $this->render('FrontOffice', 'cultures', [
            'pageTitle'   => 'Tunisie2050 – Cultures menacées',
            'currentPage' => 'cultures',
            'years'       => $years,
            'annee'       => $annee,
            'rows'        => $rows,
            'ranking'     => $ranking,
        ]);
    }

    // Use case: demander une explication (answers in JSON, called by ville.js)
    public function explication(): void
    {
        $id    = (int)($_GET['id'] ?? 0);
        $annee = (int)($_GET['annee'] ?? 0);

        $ville = Ville::findById($id);
        $ps    = Projection::findByVille($id);
        $cible = null;
        foreach ($ps as $p) {
            if ($p->annee === $annee) {
                $cible = $p;
            }
        }

        if (!$ville || !$cible) {
            $this->json(['error' => 'Données introuvables'], 404);
            return;
        }

        $this->json(['texte' => ExplicationIA::generate($ville, $ps[0], $cible)]);
    }
}