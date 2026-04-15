<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    header('Location: /');
    exit;
}

// Récupérer le monument par token
$stmt = pdo()->prepare('SELECT * FROM monuments WHERE qr_token = ? AND active = 1');
$stmt->execute([$token]);
$monument = $stmt->fetch();

if (!$monument) {
    header('Location: /');
    exit;
}

// Si l'utilisateur est connecté, a-t-il déjà joué ce monument ?
$alreadyPlayed = false;
$existingScore = null;
if (isLoggedIn()) {
    $s = pdo()->prepare('SELECT points FROM scores WHERE user_id = ? AND monument_id = ?');
    $s->execute([$_SESSION['user_id'], $monument['id']]);
    $existingScore = $s->fetch();
    $alreadyPlayed = ($existingScore !== false);
}

$pageTitle = 'Tu as trouvé : ' . $monument['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="container">
<div class="monument-found">

    <span class="badge-found">✓ Monument trouvé !</span>

    <?php if (!empty($monument['image'])): ?>
        <img src="/assets/img/<?= htmlspecialchars($monument['image']) ?>"
             alt="<?= htmlspecialchars($monument['name']) ?>"
             class="monument-img">
    <?php else: ?>
        <div class="monument-img-placeholder">🏛️</div>
    <?php endif; ?>

    <h1><?= htmlspecialchars($monument['name']) ?></h1>

    <?php if (!empty($monument['country'])): ?>
        <p style="color:var(--bordeaux);font-weight:600;margin-bottom:.5rem">
            📍 <?= htmlspecialchars($monument['country']) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($monument['description'])): ?>
        <p style="color:#555;max-width:480px;margin:0 auto 1.5rem">
            <?= htmlspecialchars($monument['description']) ?>
        </p>
    <?php endif; ?>

    <?php if ($alreadyPlayed): ?>
        <div class="alert alert-info" style="max-width:400px;margin:0 auto 1rem">
            Tu as déjà répondu à ce quiz !<br>
            Tu avais obtenu <strong><?= (int)$existingScore['points'] ?> point(s)</strong>.
        </div>
        <div class="action-buttons">
            <a href="/leaderboard.php" class="btn btn-primary btn-lg">Voir le classement</a>
            <a href="/" class="btn btn-outline" style="color:var(--black);border-color:var(--black)">Retour à l'accueil</a>
        </div>

    <?php else: ?>
        <p style="font-size:1.05rem;margin-bottom:.5rem">
            <strong>5 questions · 10 secondes chacune</strong>
        </p>
        <p style="color:#555;margin-bottom:1rem;font-size:.9rem">
            1 point par bonne réponse · 10 secondes pour répondre
        </p>
        <div class="action-buttons">
            <?php if (isLoggedIn()): ?>
                <a href="/quiz.php?token=<?= urlencode($token) ?>"
                   class="btn btn-primary btn-lg">
                    🎯 Lancer le quiz (classement)
                </a>
            <?php else: ?>
                <a href="/quiz.php?token=<?= urlencode($token) ?>&mode=anon"
                   class="btn btn-primary btn-lg">
                    🎯 Jouer anonymement
                </a>
                <a href="/login.php?redirect=<?= urlencode('/monument.php?token=' . $token) ?>"
                   class="btn btn-success btn-lg">
                    🏆 Se connecter pour le classement
                </a>
                <a href="/register.php?redirect=<?= urlencode('/monument.php?token=' . $token) ?>"
                   style="font-size:.9rem;color:var(--gray)">
                    Pas encore de compte ? S'inscrire
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
