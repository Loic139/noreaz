<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$monumentId = (int)($_GET['monument_id'] ?? 0);
if (!$monumentId) { header('Location: /admin/monuments.php'); exit; }

$monument = pdo()->prepare('SELECT * FROM monuments WHERE id=?');
$monument->execute([$monumentId]);
$monument = $monument->fetch();
if (!$monument) { header('Location: /admin/monuments.php'); exit; }

$msg = '';

// Ajout d'une question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $q  = trim($_POST['question']  ?? '');
    $c  = trim($_POST['correct']   ?? '');
    $w1 = trim($_POST['wrong1']    ?? '');
    $w2 = trim($_POST['wrong2']    ?? '');
    $w3 = trim($_POST['wrong3']    ?? '');
    if ($q && $c && $w1 && $w2 && $w3) {
        $order = (int) pdo()->prepare('SELECT COUNT(*) FROM questions WHERE monument_id=?')
                            ->execute([$monumentId]) ? 0 : 0;
        $cnt = pdo()->prepare('SELECT COUNT(*) FROM questions WHERE monument_id=?');
        $cnt->execute([$monumentId]);
        $order = (int)$cnt->fetchColumn() + 1;
        pdo()->prepare(
            'INSERT INTO questions (monument_id, question_text, answer_correct, answer_wrong1, answer_wrong2, answer_wrong3, sort_order)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([$monumentId, $q, $c, $w1, $w2, $w3, $order]);
        $msg = 'Question ajoutée.';
    }
}

// Suppression
if (isset($_GET['delete'])) {
    pdo()->prepare('DELETE FROM questions WHERE id=? AND monument_id=?')
         ->execute([(int)$_GET['delete'], $monumentId]);
    header('Location: /admin/questions.php?monument_id=' . $monumentId);
    exit;
}

$questions = pdo()->prepare('SELECT * FROM questions WHERE monument_id=? ORDER BY sort_order');
$questions->execute([$monumentId]);
$questions = $questions->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Questions</title>
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
        <h2>Questions — <?= htmlspecialchars($monument['name']) ?></h2>
        <a href="/admin/monuments.php" class="btn btn-outline" style="color:var(--black);border-color:var(--black)">← Retour</a>
    </div>

    <?php if (count($questions) >= QUIZ_QUESTIONS): ?>
        <div class="alert alert-info">Ce monument a atteint le maximum de <?= QUIZ_QUESTIONS ?> questions.</div>
    <?php else: ?>
    <!-- Formulaire d'ajout -->
    <div class="card" style="margin-bottom:1.5rem">
        <div class="card-body">
            <h3 style="margin-bottom:1rem">Ajouter une question</h3>
            <form method="post" action="/admin/questions.php?monument_id=<?= $monumentId ?>">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Question *</label>
                    <input type="text" name="question" required placeholder="Ex: En quelle année a-t-il été construit ?">
                </div>
                <div class="form-group">
                    <label>Bonne réponse *</label>
                    <input type="text" name="correct" required placeholder="La bonne réponse">
                </div>
                <div class="grid-3" style="gap:.75rem">
                    <div class="form-group" style="margin:0">
                        <label>Mauvaise réponse 1 *</label>
                        <input type="text" name="wrong1" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label>Mauvaise réponse 2 *</label>
                        <input type="text" name="wrong2" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label>Mauvaise réponse 3 *</label>
                        <input type="text" name="wrong3" required>
                    </div>
                </div>
                <br>
                <button type="submit" class="btn btn-primary">Ajouter la question</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Liste des questions -->
    <?php if (empty($questions)): ?>
        <p style="color:#555">Aucune question pour ce monument.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr><th>#</th><th>Question</th><th>Bonne réponse</th><th>Mauvaises réponses</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($questions as $i => $q): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($q['question_text']) ?></td>
                <td style="color:var(--success);font-weight:600"><?= htmlspecialchars($q['answer_correct']) ?></td>
                <td style="color:#888;font-size:.85rem">
                    <?= htmlspecialchars($q['answer_wrong1']) ?>,
                    <?= htmlspecialchars($q['answer_wrong2']) ?>,
                    <?= htmlspecialchars($q['answer_wrong3']) ?>
                </td>
                <td>
                    <a href="/admin/questions.php?monument_id=<?= $monumentId ?>&delete=<?= $q['id'] ?>"
                       class="btn" style="background:var(--danger);color:white;font-size:.8rem;padding:.3rem .7rem"
                       onclick="return confirm('Supprimer cette question ?')">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
