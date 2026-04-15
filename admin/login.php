<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!empty($_SESSION['admin'])) { header('Location: /admin/'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header('Location: /admin/');
        exit;
    }
    $error = 'Mot de passe incorrect.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Noréaz 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="form-wrap" style="margin-top:4rem">
    <h1>Administration</h1>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="form-card">
        <form method="post">
            <div class="form-group">
                <label for="password">Mot de passe admin</label>
                <input type="password" id="password" name="password" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-full">Accéder</button>
        </form>
    </div>
</div>
</body>
</html>
