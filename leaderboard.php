<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Classement — Noréaz 2026';

// Classement général
$ranking = pdo()->query(
    'SELECT u.first_name, u.last_name,
            SUM(s.points) AS total,
            COUNT(s.id)   AS monuments_joues
     FROM scores s
     JOIN users u ON u.id = s.user_id
     GROUP BY s.user_id
     ORDER BY total DESC, monuments_joues ASC'
)->fetchAll();

// Rang du joueur connecté
$myRank  = null;
$myScore = null;
if (isLoggedIn()) {
    foreach ($ranking as $i => $row) {
        // Simple comparaison par nom (à améliorer si besoin)
        $u = currentUser();
        if ($u && $row['first_name'] === $u['first_name'] && $row['last_name'] === $u['last_name']) {
            $myRank  = $i + 1;
            $myScore = $row['total'];
            break;
        }
    }
}

// Nombre total de monuments
$nbMonuments = (int) pdo()->query('SELECT COUNT(*) FROM monuments WHERE active=1')->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div class="container">

    <div class="section-header" style="margin-bottom:1.5rem">
        <h1>Classement général</h1>
        <span style="color:#555;font-size:.9rem" id="last-update">
            Mis à jour toutes les 10 secondes
        </span>
    </div>

    <?php if (isLoggedIn() && $myRank): ?>
    <div class="alert alert-info" style="margin-bottom:1.5rem">
        Ton classement : <strong>#<?= $myRank ?></strong> avec <strong><?= $myScore ?> point(s)</strong>
    </div>
    <?php endif; ?>

    <div id="leaderboard-container">
        <?php if (empty($ranking)): ?>
            <div class="alert alert-info">Personne n'a encore joué. Sois le premier !</div>
        <?php else: ?>
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Joueur</th>
                    <th>Points</th>
                    <th>Monuments joués</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ranking as $i => $row): ?>
                <tr class="rank-<?= $i+1 ?>">
                    <td>
                        <?php if ($i === 0) echo '<span class="rank-medal">🥇</span>';
                              elseif ($i === 1) echo '<span class="rank-medal">🥈</span>';
                              elseif ($i === 2) echo '<span class="rank-medal">🥉</span>';
                              else echo $i + 1; ?>
                    </td>
                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                    <td><strong><?= (int)$row['total'] ?> / <?= $nbMonuments * QUIZ_QUESTIONS ?></strong></td>
                    <td><?= (int)$row['monuments_joues'] ?> / <?= $nbMonuments ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if (!isLoggedIn()): ?>
    <div style="text-align:center;margin-top:2rem">
        <p style="color:#555;margin-bottom:1rem">Inscris-toi pour figurer dans le classement !</p>
        <a href="/register.php" class="btn btn-primary btn-lg">S'inscrire gratuitement</a>
    </div>
    <?php endif; ?>

</div>

<script>
// Rafraîchissement automatique du classement toutes les 10 secondes
setInterval(function () {
    fetch('/api/leaderboard.php')
        .then(r => r.json())
        .then(function (data) {
            if (!data.html) return;
            document.getElementById('leaderboard-container').innerHTML = data.html;
            document.getElementById('last-update').textContent =
                'Mis à jour à ' + new Date().toLocaleTimeString('fr-CH');
        })
        .catch(function () {});
}, 10000);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
