<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Réinitialiser le score d'un joueur
if (isset($_GET['reset'])) {
    pdo()->prepare('DELETE FROM scores WHERE user_id=?')->execute([(int)$_GET['reset']]);
    header('Location: /admin/users.php');
    exit;
}

// Supprimer un joueur
if (isset($_GET['delete'])) {
    pdo()->prepare('DELETE FROM users WHERE id=?')->execute([(int)$_GET['delete']]);
    header('Location: /admin/users.php');
    exit;
}

$users = pdo()->query(
    'SELECT u.id, u.first_name, u.last_name, u.email, u.created_at,
            COALESCE(SUM(s.points),0) AS total_points,
            COUNT(s.id) AS monuments_joues
     FROM users u
     LEFT JOIN scores s ON s.user_id = u.id
     GROUP BY u.id
     ORDER BY total_points DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Joueurs</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="admin-header">
    <div class="container">
        <h1>⚙️ Admin — Noréaz 2026</h1>
        <nav class="admin-nav">
            <a href="/admin/">Tableau de bord</a>
            <a href="/admin/monuments.php">Monuments</a>
            <a href="/admin/questions.php">Questions</a>
            <a href="/admin/users.php">Joueurs</a>
            <a href="/admin/logout.php" style="color:#f8d7da">Déconnexion</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="section-header">
        <h2>Joueurs inscrits (<?= count($users) ?>)</h2>
    </div>

    <?php if (empty($users)): ?>
        <p style="color:#555">Aucun joueur inscrit pour le moment.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Joueur</th>
                <th>Email</th>
                <th>Points</th>
                <th>Monuments joués</th>
                <th>Inscrit le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= (int)$u['total_points'] ?></td>
                <td><?= (int)$u['monuments_joues'] ?></td>
                <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                <td style="display:flex;gap:.5rem">
                    <a href="/admin/users.php?reset=<?= $u['id'] ?>"
                       class="btn btn-outline"
                       style="font-size:.8rem;padding:.3rem .7rem;color:var(--bordeaux);border-color:var(--bordeaux)"
                       onclick="return confirm('Réinitialiser les scores de ce joueur ?')">Reset scores</a>
                    <a href="/admin/users.php?delete=<?= $u['id'] ?>"
                       class="btn"
                       style="background:var(--danger);color:white;font-size:.8rem;padding:.3rem .7rem"
                       onclick="return confirm('Supprimer ce joueur ?')">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
