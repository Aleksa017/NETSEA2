<?php
require 'config.php';

// Verifica che l'utente sia loggato
if (!isset($_SESSION['id'])) {
    header('Location: Login.php?redirect=' . urlencode('profilo.php'));
    exit();
}

$id_utente = $_SESSION['id'];
$errors = [];
$success = false;

// Recupera i dati attuali dell'utente
$stmt = $connessione->prepare("SELECT id_utente, username, email, nome, cognome, data_registrazione FROM utente WHERE id_utente = ?");
$stmt->execute([$id_utente]);
$utente = $stmt->fetch();

if (!$utente) {
    die("Utente non trovato.");
}

// Post a cui l'utente ha messo like
try {
    $stmt_likes = $connessione->prepare("
        SELECT m.*, u.nome AS nome_pub, u.cognome AS cognome_pub
        FROM like_media lm
        JOIN media m ON lm.id_post = m.id_post
        LEFT JOIN utente u ON m.id_utente = u.id_utente
        WHERE lm.id_utente = ?
        ORDER BY m.data_pub DESC
    ");
    $stmt_likes->execute([$id_utente]);
    $post_piaciuti = $stmt_likes->fetchAll();
} catch (PDOException $e) { $post_piaciuti = []; }

// Gestione aggiornamento profilo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'aggiorna_dati') {
        $nome = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nome === '' || $cognome === '' || $email === '') {
            $errors[] = "Nome, cognome e email sono obbligatori.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email non valida.";
        } else {
            try {
                $upd = $connessione->prepare("UPDATE utente SET nome = ?, cognome = ?, email = ? WHERE id_utente = ?");
                $upd->execute([$nome, $cognome, $email, $id_utente]);
                $_SESSION['nome'] = $nome;
                $_SESSION['cognome'] = $cognome;
                $_SESSION['email'] = $email;
                $success = true;
                // Ricarica dati
                $stmt->execute([$id_utente]);
                $utente = $stmt->fetch();
            } catch (Exception $e) {
                $errors[] = "Errore durante l'aggiornamento: " . $e->getMessage();
            }
        }
    } elseif ($action === 'cambia_password') {
        $pwd_attuale = $_POST['password_attuale'] ?? '';
        $pwd_nuova = trim($_POST['password_nuova'] ?? '');
        $pwd_conferma = trim($_POST['password_conferma'] ?? '');

        // Verifica password attuale
        if (!password_verify($pwd_attuale, $utente['password_hash'])) {
            $errors[] = "Password attuale non corretta.";
        } elseif ($pwd_nuova === '' || $pwd_conferma === '') {
            $errors[] = "Nuova password e conferma sono obbligatorie.";
        } elseif (strlen($pwd_nuova) < 6) {
            $errors[] = "La nuova password deve essere lunga almeno 6 caratteri.";
        } elseif ($pwd_nuova !== $pwd_conferma) {
            $errors[] = "Le password non coincidono.";
        } else {
            $hash = password_hash($pwd_nuova, PASSWORD_BCRYPT);
            $upd = $connessione->prepare("UPDATE utente SET password_hash = ? WHERE id_utente = ?");
            $upd->execute([$hash, $id_utente]);
            $success = true;
        }
    }
    elseif ($action === 'upload_foto') {
        if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Errore nel caricamento del file.';
        } else {
            $f = $_FILES['foto'];
            if ($f['size'] > 2 * 1024 * 1024) { // 2MB
                $errors[] = 'Il file è troppo grande (max 2MB).';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($f['tmp_name']);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif'
                ];
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Formato non supportato. Usa JPG, PNG o GIF.';
                } else {
                    $ext = $allowed[$mime];
                    $dir = __DIR__ . '/uploads/profile';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $basename = $id_utente . '_' . time() . '.' . $ext;
                    $target = $dir . '/' . $basename;
                    if (!move_uploaded_file($f['tmp_name'], $target)) {
                        $errors[] = 'Impossibile salvare il file.';
                    } else {
                        // Salva percorso relativo nel DB
                        $relPath = 'uploads/profile/' . $basename;
                        try {
                            $upd = $connessione->prepare("UPDATE utente SET foto_profilo = ? WHERE id_utente = ?");
                            $upd->execute([$relPath, $id_utente]);
                            $success = true;
                            // ricarica i dati utente
                            $stmt->execute([$id_utente]);
                            $utente = $stmt->fetch();
                        } catch (Exception $e) {
                            $errors[] = 'Errore salvataggio informazioni: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo — NetSea</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>👤 Il Mio Profilo</h1>

        <?php if ($success): ?>
            <p class="alert alert-success">✅ Profilo aggiornato con successo!</p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach($errors as $e): ?>
                    <p>❌ <?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- CARD PROFILO -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar">
                    <?php if ($utente['foto_profilo'] ?? false): ?>
                        <img src="<?= htmlspecialchars($utente['foto_profilo']) ?>" alt="Foto profilo" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        🌊
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <p><strong><?= htmlspecialchars($utente['nome'] . " " . $utente['cognome']) ?></strong></p>
                    <p>@<?= htmlspecialchars($utente['username']) ?></p>
                    <p style="font-size:.85rem;color:var(--muted);">Iscritto dal <?= date('d M Y', strtotime($utente['data_registrazione'])) ?></p>
                </div>
            </div>
        </div>

        <!-- FORM UPLOAD FOTO -->
        <h2>Foto Profilo</h2>
        <div class="profile-card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_foto">
                <div class="form-group">
                    <label>Carica una foto profilo:</label>
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/gif" required>
                    <p class="hint">Formati supportati: JPG, PNG, GIF. Massimo 2MB.</p>
                </div>
                <button type="submit" class="btn">📸 Carica Foto</button>
            </form>
        </div>

        <!-- FORM MODIFICA DATI -->
        <h2>Modifica Informazioni</h2>
        <div class="profile-card">
            <form method="POST">
                <input type="hidden" name="action" value="aggiorna_dati">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome:</label>
                        <input type="text" name="nome" value="<?= htmlspecialchars($utente['nome']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Cognome:</label>
                        <input type="text" name="cognome" value="<?= htmlspecialchars($utente['cognome']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($utente['email']) ?>" required>
                </div>
                <button type="submit" class="btn">💾 Salva Modifiche</button>
            </form>
        </div>

        <!-- FORM CAMBIO PASSWORD -->
        <h2>Cambio Password</h2>
        <div class="profile-card">
            <form method="POST">
                <input type="hidden" name="action" value="cambia_password">
                <div class="form-group">
                    <label>Password Attuale:</label>
                    <input type="password" name="password_attuale" required>
                </div>
                <div class="form-group">
                    <label>Nuova Password:</label>
                    <input type="password" name="password_nuova" required>
                </div>
                <div class="form-group">
                    <label>Conferma Password:</label>
                    <input type="password" name="password_conferma" required>
                </div>
                <button type="submit" class="btn">🔐 Cambia Password</button>
            </form>
        </div>

        <!-- POST PIACIUTI -->
        <h2>❤️ Post che ti sono piaciuti</h2>
        <div class="profile-card">
          <?php if (empty($post_piaciuti)): ?>
            <p style="color:var(--muted);font-size:.875rem;">Non hai ancora messo like a nessun contenuto. <a href="feed.php">Vai al feed →</a></p>
          <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.75rem;">
            <?php foreach ($post_piaciuti as $lp):
              $isImg = !empty($lp['url']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $lp['url']);
              $isVid = !empty($lp['url']) && preg_match('/\.(mp4|webm)$/i', $lp['url']);
              $pub   = trim(($lp['nome_pub'] ?? '') . ' ' . ($lp['cognome_pub'] ?? '')) ?: 'NetSea';
            ?>
            <a href="feed.php" style="display:block;text-decoration:none;background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.1);border-radius:12px;overflow:hidden;transition:border-color .2s,transform .2s;" onmouseover="this.style.borderColor='rgba(114,215,240,.3)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='rgba(114,215,240,.1)';this.style.transform='none'">
              <div style="height:120px;background:linear-gradient(135deg,var(--ocean),var(--deep));display:flex;align-items:center;justify-content:center;font-size:3rem;position:relative;overflow:hidden;">
                <?php if ($isImg): ?>
                  <img src="<?= htmlspecialchars($lp['url']) ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;" alt="">
                <?php elseif ($isVid): ?>
                  <span>📹</span>
                <?php else: ?>
                  <span>🌊</span>
                <?php endif; ?>
              </div>
              <div style="padding:.75rem;">
                <p style="color:var(--pearl);font-size:.85rem;font-weight:500;line-height:1.3;margin-bottom:.3rem;"><?= htmlspecialchars(mb_substr($lp['titolo'] ?? '', 0, 50)) ?></p>
                <p style="color:var(--muted);font-size:.72rem;">di <?= htmlspecialchars($pub) ?></p>
              </div>
            </a>
            <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div>
            <a href="index.php" class="btn btn-secondary btn-back" style="text-decoration:none;">← Torna alla home</a>
        </div>
    </div>
</body>
</html>