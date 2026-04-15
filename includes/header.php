<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <meta name="description" content="Chasse aux trésors — Giron des Jeunesses Sarinoises, 24-28 juin 2026">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="/" class="logo">
            <span class="logo-noreaz">NORÉAZ</span>
            <span class="logo-sub">LES MONUMENTS</span>
        </a>
        <nav class="site-nav">
            <a href="/">Accueil</a>
            <a href="/leaderboard.php">Classement</a>
            <?php if (isLoggedIn()): ?>
                <span class="nav-user">👤 <?= htmlspecialchars(currentUser()['first_name'] ?? '') ?></span>
                <a href="/logout.php" class="btn btn-outline">Déconnexion</a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-outline">Connexion</a>
                <a href="/register.php" class="btn btn-primary">S'inscrire</a>
            <?php endif; ?>
        </nav>
        <button class="nav-toggle" aria-label="Menu">&#9776;</button>
    </div>
</header>
<main class="site-main">
