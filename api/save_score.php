<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$monumentId = (int)($body['monument_id'] ?? 0);
$score      = (int)($body['score']       ?? 0);

if (!$monumentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Monument invalide']);
    exit;
}

// Vérifier que le monument existe et est actif
$stmt = pdo()->prepare('SELECT id FROM monuments WHERE id=? AND active=1');
$stmt->execute([$monumentId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Monument introuvable']);
    exit;
}

// Vérifier qu'il n'a pas déjà joué
$check = pdo()->prepare('SELECT id FROM scores WHERE user_id=? AND monument_id=?');
$check->execute([$_SESSION['user_id'], $monumentId]);
if ($check->fetch()) {
    echo json_encode(['status' => 'already_played']);
    exit;
}

// Clamp le score entre 0 et QUIZ_QUESTIONS
$score = max(0, min($score, QUIZ_QUESTIONS));

// Enregistrer
pdo()->prepare('INSERT INTO scores (user_id, monument_id, points) VALUES (?,?,?)')
     ->execute([$_SESSION['user_id'], $monumentId, $score]);

echo json_encode(['status' => 'ok', 'points' => $score]);
