<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

$token = trim($_GET['token'] ?? '');
$mode  = $_GET['mode'] ?? 'auth'; // 'anon' ou 'auth'

if (empty($token)) { header('Location: /'); exit; }

// Récupérer le monument
$stmt = pdo()->prepare('SELECT * FROM monuments WHERE qr_token = ? AND active = 1');
$stmt->execute([$token]);
$monument = $stmt->fetch();
if (!$monument) { header('Location: /'); exit; }

// Si mode auth, l'utilisateur doit être connecté
if ($mode !== 'anon' && !isLoggedIn()) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérifier que le joueur connecté n'a pas déjà joué
if (isLoggedIn()) {
    $s = pdo()->prepare('SELECT id FROM scores WHERE user_id = ? AND monument_id = ?');
    $s->execute([$_SESSION['user_id'], $monument['id']]);
    if ($s->fetch()) {
        header('Location: /monument.php?token=' . urlencode($token));
        exit;
    }
}

// Récupérer les questions
$qStmt = pdo()->prepare(
    'SELECT question_text, answer_correct, answer_wrong1, answer_wrong2, answer_wrong3
     FROM questions WHERE monument_id = ? ORDER BY sort_order LIMIT ' . QUIZ_QUESTIONS
);
$qStmt->execute([$monument['id']]);
$questions = $qStmt->fetchAll();

if (empty($questions)) {
    header('Location: /monument.php?token=' . urlencode($token));
    exit;
}

// Préparer les données JS
$jsQuestions = array_map(fn($q) => [
    'question' => $q['question_text'],
    'correct'  => $q['answer_correct'],
    'wrong1'   => $q['answer_wrong1'],
    'wrong2'   => $q['answer_wrong2'],
    'wrong3'   => $q['answer_wrong3'],
], $questions);

$saveUrl = ($mode === 'anon') ? '' : '/api/save_score.php';

$pageTitle = 'Quiz — ' . $monument['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="quiz-wrap"
     data-timer="<?= QUIZ_TIMER ?>"
     id="body-data"
     data-save-url="<?= $saveUrl ?>"
     data-monument-id="<?= (int)$monument['id'] ?>">

    <div class="quiz-header">
        <h2><?= htmlspecialchars($monument['name']) ?></h2>
        <p style="color:#555;font-size:.9rem">
            <?= count($questions) ?> questions · <?= QUIZ_TIMER ?> secondes par question · 1 point par bonne réponse
        </p>
    </div>

    <!-- Section quiz -->
    <div id="quiz-section">
        <div class="quiz-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill" style="width:0%"></div>
            </div>
            <span class="progress-label" id="progress-label">Question 1 / <?= count($questions) ?></span>
        </div>

        <div class="timer-wrap">
            <div class="timer-circle" id="timer-circle">
                <span id="timer-count"><?= QUIZ_TIMER ?></span>
            </div>
        </div>

        <div class="question-card">
            <p class="question-text" id="question-text"></p>
            <ul class="answers-list" id="answers-list"></ul>
        </div>
    </div>

    <!-- Section résultat -->
    <div id="result-section" style="display:none">
        <div class="result-wrap">
            <h2>Quiz terminé !</h2>
            <div class="score-badge" id="score-value">0 / <?= count($questions) ?></div>
            <p id="result-message"></p>

            <!-- Partage réseaux sociaux -->
            <div class="share-block">
                <p class="share-label">📣 Partage ta découverte !</p>
                <div class="share-buttons">
                    <a id="share-whatsapp" href="#" target="_blank" class="share-btn share-whatsapp">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.118 1.528 5.855L.057 23.882l6.188-1.443A11.944 11.944 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.886 0-3.65-.502-5.178-1.378l-.371-.22-3.674.857.895-3.558-.242-.38A9.944 9.944 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        WhatsApp
                    </a>
                    <a id="share-facebook" href="#" target="_blank" class="share-btn share-facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Facebook
                    </a>
                    <a id="share-twitter" href="#" target="_blank" class="share-btn share-twitter">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        X / Twitter
                    </a>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:.75rem;margin-top:1rem">
                <a href="/leaderboard.php" class="btn btn-primary btn-lg">Voir le classement</a>
                <a href="/" class="btn btn-outline" style="color:var(--black);border-color:var(--black)">Retour à l'accueil</a>
            </div>
        </div>
    </div>

</div>

<script>
window.QUIZ_DATA      = <?= json_encode($jsQuestions, JSON_UNESCAPED_UNICODE) ?>;
window.MONUMENT_NAME  = <?= json_encode($monument['name'], JSON_UNESCAPED_UNICODE) ?>;
window.SHARE_URL      = <?= json_encode(APP_URL) ?>;
document.body.dataset.timer      = '<?= QUIZ_TIMER ?>';
document.body.dataset.saveUrl    = '<?= $saveUrl ?>';
document.body.dataset.monumentId = '<?= (int)$monument['id'] ?>';
</script>
<script src="/assets/js/quiz.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
