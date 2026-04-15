<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: /'); exit; }

$redirect = $_GET['redirect'] ?? '/';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = strtolower(trim($_POST['email']    ?? ''));
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm']  ?? '';

    if (!$firstName || !$lastName || !$email || !$password) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        // Vérifier que l'email n'existe pas
        $check = pdo()->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Cette adresse e-mail est déjà utilisée.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = pdo()->prepare(
                'INSERT INTO users (email, first_name, last_name, password) VALUES (?, ?, ?, ?)'
            );
            $ins->execute([$email, $firstName, $lastName, $hash]);
            $_SESSION['user_id'] = pdo()->lastInsertId();
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$pageTitle = 'Inscription — Noréaz 2026';
include __DIR__ . '/includes/header.php';
?>

<div class="form-wrap">
    <h1>S'inscrire</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post" action="/register.php?redirect=<?= urlencode($redirect) ?>">
            <div class="grid-2">
                <div class="form-group">
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" name="first_name"
                           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                           required autocomplete="given-name">
                </div>
                <div class="form-group">
                    <label for="last_name">Nom</label>
                    <input type="text" id="last_name" name="last_name"
                           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                           required autocomplete="family-name">
                </div>
            </div>
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe <small style="color:#888">(6 caractères min.)</small></label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirm">Confirmer le mot de passe</label>
                <input type="password" id="confirm" name="confirm" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-full">Créer mon compte</button>
        </form>
        <div class="form-footer">
            Déjà un compte ? <a href="/login.php?redirect=<?= urlencode($redirect) ?>">Se connecter</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
