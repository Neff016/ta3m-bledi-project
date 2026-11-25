<?php
/*
 * Configuration de l'envoi d'emails (code « mot de passe oublié »)
 * ---------------------------------------------------------------
 * 1. Copiez ce fichier et renommez la copie en : Config/mail.php
 * 2. Mettez votre adresse Gmail et votre MOT DE PASSE D'APPLICATION Gmail
 *    (16 lettres, créé sur https://myaccount.google.com/apppasswords).
 *    ⚠ Pas votre mot de passe Gmail normal.
 * 3. Config/mail.php est dans .gitignore : il ne part jamais sur GitHub.
 */
const MAIL_HOTE         = 'smtp.gmail.com';
const MAIL_PORT         = 465;                       // SSL
const MAIL_UTILISATEUR  = 'yomnaghannem0@gmail.com';
const MAIL_MOT_DE_PASSE = 'dapb afax wnjz klxw';     // mot de passe d'application
const MAIL_NOM_EXPEDITEUR = 'Taam Bledi';
