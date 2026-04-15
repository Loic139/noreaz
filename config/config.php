<?php
// Configuration générale de l'application
define('APP_NAME',    'Noréaz 2026 — Les Monuments');
define('APP_URL',     'https://noreaz.digitme.fun');
define('APP_VERSION', '1.0.0');

// Durée du timer par question (secondes)
define('QUIZ_TIMER', 10);

// Nombre de questions par monument
define('QUIZ_QUESTIONS', 5);

// Mot de passe admin (à changer avant mise en production !)
define('ADMIN_PASSWORD', 'noreaz2026admin');

// Fuseau horaire
date_default_timezone_set('Europe/Zurich');
