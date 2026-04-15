<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Noréaz 2026 — Chasse aux trésors';

// Récupérer les monuments actifs
$monuments = pdo()
    ->query('SELECT id, name, country, description, image, hint FROM monuments WHERE active = 1 ORDER BY id')
    ->fetchAll();

// Monuments trouvés par l'utilisateur connecté
$foundIds = [];
if (isLoggedIn()) {
    $stmt = pdo()->prepare('SELECT monument_id FROM scores WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $foundIds = array_column($stmt->fetchAll(), 'monument_id');
}

// Top 5 du classement pour la page d'accueil
$top5 = pdo()->query(
    'SELECT u.first_name, u.last_name, SUM(s.points) AS total
     FROM scores s
     JOIN users u ON u.id = s.user_id
     GROUP BY s.user_id
     ORDER BY total DESC
     LIMIT 5'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <div class="container">
        <h1>Noréaz — <span>Les Monuments</span></h1>
        <p>Pars à la découverte des monuments cachés dans le village !<br>
           Scanne les QR codes, réponds aux questions et grimpe dans le classement.</p>
        <span class="hero-dates">24 – 28 juin 2026</span>
    </div>
</div>

<div class="container">

    <!-- Comment jouer -->
    <section style="margin-bottom:2.5rem">
        <h2 style="margin-bottom:1rem">Comment jouer ?</h2>
        <div class="grid-3">
            <div class="card"><div class="card-body" style="text-align:center">
                <div style="font-size:2.5rem;margin-bottom:.5rem">🔍</div>
                <h3>1. Trouve</h3>
                <p style="color:#555;font-size:.9rem">Pars à la découverte des monuments miniatures cachés dans le village de Noréaz.</p>
            </div></div>
            <div class="card"><div class="card-body" style="text-align:center">
                <div style="font-size:2.5rem;margin-bottom:.5rem">📱</div>
                <h3>2. Scanne</h3>
                <p style="color:#555;font-size:.9rem">Scanne le QR code sur le monument avec ton téléphone.</p>
            </div></div>
            <div class="card"><div class="card-body" style="text-align:center">
                <div style="font-size:2.5rem;margin-bottom:.5rem">🏆</div>
                <h3>3. Réponds</h3>
                <p style="color:#555;font-size:.9rem">Réponds aux 5 questions en moins de 10 secondes chacune et marque des points !</p>
            </div></div>
        </div>
    </section>

    <!-- Monuments -->
    <section style="margin-bottom:2.5rem">
        <div class="section-header">
            <h2>Les <?= count($monuments) ?> monuments à trouver</h2>
            <?php if (isLoggedIn()): ?>
                <span style="color:var(--bordeaux);font-weight:600">
                    <?= count($foundIds) ?> / <?= count($monuments) ?> trouvé<?= count($foundIds) > 1 ? 's' : '' ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if (empty($monuments)): ?>
            <p style="color:#555">Les monuments seront bientôt révélés…</p>
        <?php else: ?>
        <div class="grid-3">
            <?php foreach ($monuments as $m):
                $found = in_array($m['id'], $foundIds);
            ?>
            <div class="monument-card <?= $found ? 'monument-found-card' : 'monument-hidden-card' ?>">
                <?php
                $hasImg  = !empty($m['image']) && file_exists(__DIR__ . '/assets/img/monuments/' . $m['image']);
                $imgSrc  = $hasImg ? '/assets/img/monuments/' . htmlspecialchars($m['image']) : null;
                ?>
                <?php if ($found): ?>
                    <!-- Monument trouvé -->
                    <div class="card-img">
                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($m['name']) ?>"
                                 style="width:100%;height:160px;object-fit:cover">
                        <?php else: ?>
                            🏛️
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="country"><?= htmlspecialchars($m['country'] ?? '') ?></div>
                        <div class="card-title"><?= htmlspecialchars($m['name']) ?></div>
                        <span style="color:var(--success);font-size:.85rem;font-weight:600">✓ Trouvé !</span>
                    </div>
                <?php else: ?>
                    <!-- Monument non trouvé — photo floutée ou placeholder -->
                    <div class="card-img card-img-hidden">
                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>" alt="Monument mystère"
                                 style="width:100%;height:160px;object-fit:cover;filter:blur(14px) brightness(.7);transform:scale(1.1)">
                        <?php else: ?>
                            ❓
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="country" style="color:var(--gray-300)">— — —</div>
                        <div class="card-title" style="color:var(--gray-300);letter-spacing:.2em">? ? ? ? ?</div>
                        <?php if (!empty($m['hint'])): ?>
                            <button class="btn-hint" onclick="toggleHint(this)"
                                    data-hint="<?= htmlspecialchars($m['hint']) ?>">
                                💡 Voir un indice
                            </button>
                            <p class="hint-text" style="display:none"></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!isLoggedIn()): ?>
            <p style="text-align:center;color:#555;margin-top:1.25rem;font-size:.95rem">
                <a href="/register.php">Inscris-toi</a> ou <a href="/login.php">connecte-toi</a> pour suivre ta progression.
            </p>
        <?php endif; ?>
    </section>

    <!-- Mini classement -->
    <?php if (!empty($top5)): ?>
    <section style="margin-bottom:2rem">
        <div class="section-header">
            <h2>Top 5 du classement</h2>
            <a href="/leaderboard.php" class="btn btn-outline" style="color:var(--bordeaux);border-color:var(--bordeaux)">Voir tout le classement</a>
        </div>
        <table class="leaderboard-table">
            <thead>
                <tr><th>#</th><th>Joueur</th><th>Points</th></tr>
            </thead>
            <tbody>
                <?php foreach ($top5 as $i => $row): ?>
                <tr class="rank-<?= $i+1 ?>">
                    <td>
                        <?php if ($i === 0) echo '<span class="rank-medal">🥇</span>';
                              elseif ($i === 1) echo '<span class="rank-medal">🥈</span>';
                              elseif ($i === 2) echo '<span class="rank-medal">🥉</span>';
                              else echo $i + 1; ?>
                    </td>
                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                    <td><strong><?= (int)$row['total'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
