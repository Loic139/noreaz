<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$stats = [
    'monuments' => (int) pdo()->query('SELECT COUNT(*) FROM monuments')->fetchColumn(),
    'questions' => (int) pdo()->query('SELECT COUNT(*) FROM questions')->fetchColumn(),
    'users'     => (int) pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'scores'    => (int) pdo()->query('SELECT SUM(points) FROM scores')->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Tableau de bord</title>
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
    <h2 style="margin-bottom:1.5rem">Tableau de bord</h2>

    <div class="grid-3" style="margin-bottom:2rem">
        <div class="card"><div class="card-body" style="text-align:center">
            <div style="font-size:2.5rem;color:var(--bordeaux);font-family:'Oswald',sans-serif"><?= $stats['monuments'] ?></div>
            <div style="font-weight:600">Monuments</div>
        </div></div>
        <div class="card"><div class="card-body" style="text-align:center">
            <div style="font-size:2.5rem;color:var(--bordeaux);font-family:'Oswald',sans-serif"><?= $stats['users'] ?></div>
            <div style="font-weight:600">Joueurs inscrits</div>
        </div></div>
        <div class="card"><div class="card-body" style="text-align:center">
            <div style="font-size:2.5rem;color:var(--bordeaux);font-family:'Oswald',sans-serif"><?= $stats['scores'] ?></div>
            <div style="font-weight:600">Points marqués</div>
        </div></div>
    </div>

    <!-- QR Codes -->
    <section style="margin-bottom:2rem">
        <div class="section-header">
            <h2>QR Codes des monuments</h2>
            <a href="/admin/monuments.php" class="btn btn-primary">Gérer les monuments</a>
        </div>
        <div class="grid-3">
            <?php
            $monuments = pdo()->query('SELECT id, name, qr_token, active FROM monuments ORDER BY id')->fetchAll();
            foreach ($monuments as $m):
                $url = APP_URL . '/monument.php?token=' . urlencode($m['qr_token']);
            ?>
            <div class="card">
                <div class="card-body" style="text-align:center">
                    <h3 style="margin-bottom:.75rem"><?= htmlspecialchars($m['name']) ?></h3>
                    <div id="qr-<?= $m['id'] ?>"></div>
                    <p style="font-size:.75rem;color:#888;margin-top:.5rem;word-break:break-all"><?= htmlspecialchars($url) ?></p>
                    <span class="badge <?= $m['active'] ? 'badge-active' : 'badge-inactive' ?>">
                        <?= $m['active'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Classement complet -->
    <section>
        <h2 style="margin-bottom:1rem">Classement en direct</h2>
        <?php
        $ranking = pdo()->query(
            'SELECT u.first_name, u.last_name, SUM(s.points) AS total, COUNT(s.id) AS nb
             FROM scores s JOIN users u ON u.id = s.user_id
             GROUP BY s.user_id ORDER BY total DESC'
        )->fetchAll();
        ?>
        <?php if (empty($ranking)): ?>
            <p style="color:#555">Aucun score pour le moment.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead><tr><th>#</th><th>Joueur</th><th>Points</th><th>Monuments joués</th></tr></thead>
            <tbody>
            <?php foreach ($ranking as $i => $r): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><strong><?= (int)$r['total'] ?></strong></td>
                    <td><?= (int)$r['nb'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
</div>

<!-- QR Code generator (lib JS légère) -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
<?php foreach ($monuments as $m): $url = APP_URL . '/monument.php?token=' . urlencode($m['qr_token']); ?>
new QRCode(document.getElementById('qr-<?= $m['id'] ?>'), {
    text: '<?= addslashes($url) ?>',
    width: 160, height: 160,
    colorDark: '#1A1A1A', colorLight: '#FFFFFF'
});
<?php endforeach; ?>
</script>
</body>
</html>
