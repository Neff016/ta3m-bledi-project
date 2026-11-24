<?php
// Test accounts (passwords are hashed with password_hash, never stored in clear text).
// Admin : admin@tunisie2050.tn / Admin@2050
// User  : user@tunisie2050.tn  / User@2050
return [
    [
        'idUtilisateur'   => 1,
        'nom'             => 'Admin',
        'prenom'          => 'Tunisie2050',
        'email'           => 'admin@tunisie2050.tn',
        'motDePasse'      => password_hash('Admin@2050', PASSWORD_DEFAULT),
        'telephone'       => '',
        'role'            => 'admin',
        'statut'          => 'actif',
        'dateInscription' => date('Y-m-d'),
    ],
    [
        'idUtilisateur'   => 2,
        'nom'             => 'Ben Salah',
        'prenom'          => 'Amine',
        'email'           => 'user@tunisie2050.tn',
        'motDePasse'      => password_hash('User@2050', PASSWORD_DEFAULT),
        'telephone'       => '',
        'role'            => 'utilisateur',
        'statut'          => 'actif',
        'dateInscription' => date('Y-m-d'),
    ],
];
