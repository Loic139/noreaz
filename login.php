<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: /'); exit; }

$redirect = $_GET['redirect'] ?? '/';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = pdo()->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Email ou mot de passe incorrect.';
    }
}

$pageTitle = 'Connexion — Noréaz 2026';
include __DIR__ . '/includes/header.php';
?>

<div class="form-wrap">
    <h1>Se connecter</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post" action="/login.php?redirect=<?= urlencode($redirect) ?>">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-full">Se connecter</button>
        </form>
        <div class="form-footer">
            Pas encore de compte ? <a href="/register.php?redirect=<?= urlencode($redirect) ?>">S'inscrire</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
