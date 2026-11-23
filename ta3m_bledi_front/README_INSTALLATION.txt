TA3M BLEDI - FRONT OFFICE

1) Extraire le dossier ta3m_bledi_front dans C:\xampp\htdocs\.
2) Importer UNE SEULE FOIS Database/ta3m_bledi.sql dans phpMyAdmin (base ta3m_bledi).
3) Vérifier Shared/app/config/database.php (root / mot de passe MySQL).
4) Démarrer Apache et MySQL dans XAMPP.
5) Ouvrir http://localhost/ta3m_bledi_front/Front_Office/index.php?page=ateliers

Inscription depuis un atelier : cliquer sur Se connecter sur la carte choisie, se connecter ou créer un compte, puis se connecter. L'inscription est alors créée automatiquement et visible dans le Back Office > Inscriptions.

Le Front Office utilise TA3M_FRONT_SESSION et ne contient aucune route de gestion Admin. Ne pas importer le SQL une deuxième fois si la base existe déjà.
