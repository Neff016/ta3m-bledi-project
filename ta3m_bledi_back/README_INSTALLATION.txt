TA3M BLEDI - BACK OFFICE

1) Extraire le dossier ta3m_bledi_back dans C:\xampp\htdocs\.
2) Utiliser la base ta3m_bledi déjà créée depuis le SQL du Front Office. Ne pas réimporter le SQL si elle existe.
3) Vérifier Shared/app/config/database.php (root / mot de passe MySQL).
4) Démarrer Apache et MySQL dans XAMPP.
5) Créer l'administrateur une seule fois : ouvrir le terminal dans ce dossier et exécuter :
   C:\xampp\php\php.exe setup_admin.php
6) Supprimer setup_admin.php après création du compte.
7) Ouvrir http://localhost/ta3m_bledi_back/Back_Office/index.php?page=login

Compte initial : admin@ta3mbledi.tn / Admin123! (changer le mot de passe immédiatement).

Le Back Office utilise TA3M_ADMIN_SESSION et seules ses routes exigent le rôle admin. Le lien Voir le Front Office ouvre l'espace public. Le Front Office ne contient aucun lien ni route vers l'administration.
