<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

$ranking = pdo()->query(
    'SELECT u.first_name, u.last_name, SUM(s.points) AS total, COUNT(s.id) AS nb
     FROM scores s JOIN users u ON u.id = s.user_id
     GROUP BY s.user_id ORDER BY total DESC'
)->fetchAll();

$nbMonuments = (int) pdo()->query('SELECT COUNT(*) FROM monuments WHERE active=1')->fetchColumn();

ob_start();
if (empty($ranking)): ?>
    <div class="alert alert-info">Personne n'a encore joué. Sois le premier !</div>
<?php else: ?>
<table class="leaderboard-table">
    <thead>
        <tr><th style="width:60px">#</th><th>Joueur</th><th>Points</th><th>Monuments joués</th></tr>
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
        <td><?= (int)$row['nb'] ?> / <?= $nbMonuments ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif;

$html = ob_get_clean();
echo json_encode(['html' => $html]);
