<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$msg = '';

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name    = trim($_POST['name']    ?? '');
    $country = trim($_POST['country'] ?? '');
    $desc    = trim($_POST['desc']    ?? '');
    $hint    = trim($_POST['hint']    ?? '');
    if ($name) {
        $slug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $token = bin2hex(random_bytes(16));
        pdo()->prepare(
            'INSERT INTO monuments (name, slug, description, country, hint, qr_token) VALUES (?,?,?,?,?,?)'
        )->execute([$name, $slug, $desc, $country, $hint, $token]);
        $msg = 'Monument ajouté avec succès.';
    }
}

// Activation / désactivation
if (isset($_GET['toggle'])) {
    $id   = (int)$_GET['toggle'];
    $cur  = pdo()->prepare('SELECT active FROM monuments WHERE id=?');
    $cur->execute([$id]);
    $row  = $cur->fetch();
    if ($row) {
        pdo()->prepare('UPDATE monuments SET active=? WHERE id=?')
             ->execute([$row['active'] ? 0 : 1, $id]);
    }
    header('Location: /admin/monuments.php');
    exit;
}

// Suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    pdo()->prepare('DELETE FROM monuments WHERE id=?')->execute([$id]);
    header('Location: /admin/monuments.php');
    exit;
}

$monuments = pdo()->query(
    'SELECT m.*, COUNT(q.id) AS nb_questions
     FROM monuments m LEFT JOIN questions q ON q.monument_id = m.id
     GROUP BY m.id ORDER BY m.id'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Monuments</title>
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
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="section-header">
        <h2>Monuments</h2>
    </div>

    <!-- Formulaire d'ajout -->
    <div class="card" style="margin-bottom:1.5rem">
        <div class="card-body">
            <h3 style="margin-bottom:1rem">Ajouter un monument</h3>
            <form method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin:0">
                    <label>Nom du monument *</label>
                    <input type="text" name="name" required placeholder="Ex: Colisée de Rome">
                </div>
                <div class="form-group" style="margin:0">
                    <label>Pays</label>
                    <input type="text" name="country" placeholder="Ex: Italie">
                </div>
                <div class="form-group" style="margin:0;grid-column:1/-1">
                    <label>Description courte</label>
                    <input type="text" name="desc" placeholder="Courte description pour la page de découverte">
                </div>
                <div class="form-group" style="margin:0;grid-column:1/-1">
                    <label>Indice d'emplacement 💡</label>
                    <input type="text" name="hint" placeholder="Ex: Je suis caché près de la fontaine du village…">
                </div>
                <div style="grid-column:1/-1">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Monument</th>
                <th>Pays</th>
                <th>Questions</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($monuments as $m): ?>
            <tr>
                <td><?= $m['id'] ?></td>
                <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                <td><?= htmlspecialchars($m['country'] ?? '—') ?></td>
                <td><?= (int)$m['nb_questions'] ?> / <?= QUIZ_QUESTIONS ?></td>
                <td>
                    <span class="badge <?= $m['active'] ? 'badge-active' : 'badge-inactive' ?>">
                        <?= $m['active'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </td>
                <td style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <a href="/admin/questions.php?monument_id=<?= $m['id'] ?>" class="btn btn-outline" style="color:var(--bordeaux);border-color:var(--bordeaux);font-size:.8rem;padding:.3rem .7rem">Questions</a>
                    <a href="/admin/monuments.php?toggle=<?= $m['id'] ?>"
                       class="btn btn-outline"
                       style="font-size:.8rem;padding:.3rem .7rem;color:var(--black);border-color:var(--black)"
                       onclick="return confirm('Changer le statut de ce monument ?')">
                        <?= $m['active'] ? 'Désactiver' : 'Activer' ?>
                    </a>
                    <a href="/admin/monuments.php?delete=<?= $m['id'] ?>"
                       class="btn"
                       style="background:var(--danger);color:white;font-size:.8rem;padding:.3rem .7rem"
                       onclick="return confirm('Supprimer ce monument et toutes ses questions ?')">
                        Supprimer
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
