<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$msg     = '';
$msgType = 'success';

// Fonction upload image
function uploadImage(array $file, int $monumentId): ?string {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // 5 Mo max

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'monument_' . $monumentId . '.' . strtolower($ext);
    $dest     = __DIR__ . '/../assets/img/monuments/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $filename;
    }
    return null;
}

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
        $newId = (int) pdo()->lastInsertId();

        // Upload image si fournie
        if (!empty($_FILES['image']['name'])) {
            $img = uploadImage($_FILES['image'], $newId);
            if ($img) {
                pdo()->prepare('UPDATE monuments SET image=? WHERE id=?')->execute([$img, $newId]);
            }
        }
        $msg = 'Monument ajouté avec succès.';
    }
}

// Upload / remplacement photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    $id = (int)($_POST['monument_id'] ?? 0);
    if ($id && !empty($_FILES['image']['name'])) {
        $img = uploadImage($_FILES['image'], $id);
        if ($img) {
            pdo()->prepare('UPDATE monuments SET image=? WHERE id=?')->execute([$img, $id]);
            $msg = 'Photo mise à jour.';
        } else {
            $msg     = 'Format invalide ou fichier trop lourd (max 5 Mo, JPG/PNG/WebP).';
            $msgType = 'danger';
        }
    }
    header('Location: /admin/monuments.php?msg=' . urlencode($msg));
    exit;
}

// Activation / désactivation
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = pdo()->prepare('SELECT active FROM monuments WHERE id=?');
    $cur->execute([$id]);
    $row = $cur->fetch();
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

if (isset($_GET['msg'])) $msg = $_GET['msg'];

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
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .thumb { width:52px; height:52px; object-fit:cover; border-radius:6px; border:1px solid var(--gray-300); }
        .thumb-empty { width:52px; height:52px; background:var(--gray-100); border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; border:1px dashed var(--gray-300); }
        .upload-form { display:inline-flex; align-items:center; gap:.4rem; }
        .upload-form input[type=file] { display:none; }
        .upload-label { cursor:pointer; font-size:.78rem; font-weight:600; color:var(--red); border:1.5px dashed var(--red); padding:.25rem .6rem; border-radius:var(--radius-full); transition:background .2s; white-space:nowrap; }
        .upload-label:hover { background:var(--red-glow); }
    </style>
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
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="section-header"><h2>Monuments</h2></div>

    <!-- Formulaire d'ajout -->
    <div class="card" style="margin-bottom:1.5rem">
        <div class="card-body">
            <h3 style="margin-bottom:1rem">Ajouter un monument</h3>
            <form method="post" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
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
                <div class="form-group" style="margin:0;grid-column:1/-1">
                    <label>Photo du monument <small style="color:var(--gray-500)">(JPG, PNG ou WebP, max 5 Mo)</small></label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" style="font-size:.9rem">
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
                <th>Photo</th>
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
                <td>
                    <?php
                    $imgPath = __DIR__ . '/../assets/img/monuments/' . $m['image'];
                    if (!empty($m['image']) && file_exists($imgPath)):
                    ?>
                        <img src="/assets/img/monuments/<?= htmlspecialchars($m['image']) ?>"
                             class="thumb" alt="<?= htmlspecialchars($m['name']) ?>">
                    <?php else: ?>
                        <div class="thumb-empty">🏛️</div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?= htmlspecialchars($m['name']) ?></strong>
                    <!-- Formulaire upload photo -->
                    <form method="post" enctype="multipart/form-data" class="upload-form" style="margin-top:.4rem">
                        <input type="hidden" name="action" value="upload">
                        <input type="hidden" name="monument_id" value="<?= $m['id'] ?>">
                        <input type="file" name="image" id="img-<?= $m['id'] ?>"
                               accept="image/jpeg,image/png,image/webp"
                               onchange="this.form.submit()">
                        <label for="img-<?= $m['id'] ?>" class="upload-label">
                            📷 <?= empty($m['image']) ? 'Ajouter photo' : 'Changer photo' ?>
                        </label>
                    </form>
                </td>
                <td><?= htmlspecialchars($m['country'] ?? '—') ?></td>
                <td><?= (int)$m['nb_questions'] ?> / <?= QUIZ_QUESTIONS ?></td>
                <td>
                    <span class="badge <?= $m['active'] ? 'badge-active' : 'badge-inactive' ?>">
                        <?= $m['active'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </td>
                <td style="display:flex;gap:.4rem;flex-wrap:wrap">
                    <a href="/admin/questions.php?monument_id=<?= $m['id'] ?>"
                       class="btn btn-ghost" style="font-size:.8rem;padding:.3rem .7rem">Questions</a>
                    <a href="/admin/monuments.php?toggle=<?= $m['id'] ?>"
                       class="btn btn-dark" style="font-size:.8rem;padding:.3rem .7rem"
                       onclick="return confirm('Changer le statut de ce monument ?')">
                        <?= $m['active'] ? 'Désactiver' : 'Activer' ?>
                    </a>
                    <a href="/admin/monuments.php?delete=<?= $m['id'] ?>"
                       class="btn" style="background:var(--danger);color:white;font-size:.8rem;padding:.3rem .7rem"
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
