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
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo — NetSea</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
      *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
      :root{
        --ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;
        --text:#c5e4f5;--muted:#5d9ab8;--kelp:#2cb89b;--coral:#e05a3a;--ease:cubic-bezier(.25,.46,.45,.94);
      }
      body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);padding:2rem 2.5rem;}
      h1{font-family:'Cormorant Garamond',serif;font-size:2.2rem;color:var(--pearl);margin-bottom:1.5rem;}
      h2{font-family:'Cormorant Garamond',serif;font-size:1.5rem;color:var(--foam);margin-top:2rem;margin-bottom:1rem;}
      .container{max-width:700px;}
      .profile-card{background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.15);border-radius:12px;padding:1.5rem;margin-bottom:2rem;}
      .profile-header{display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;}
      .avatar{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#1b9fd4,#2cb89b);display:flex;align-items:center;justify-content:center;font-size:2.5rem;flex-shrink:0;}
      .profile-info p{margin:.3rem 0;color:var(--muted);font-size:.95rem;}
      .profile-info strong{color:var(--foam);}
      .alert{padding:1rem;margin:.5rem 0;border-radius:10px;}
      .alert-success{background:rgba(44,184,155,.1);border:1px solid rgba(44,184,155,.3);color:#3dd4ae;}
      .alert-error{background:rgba(224,90,58,.1);border:1px solid rgba(224,90,58,.3);color:#e8836a;}
      .form-group{margin-bottom:1.5rem;}
      label{display:block;margin-bottom:.5rem;color:var(--foam);font-weight:500;}
      input{width:100%;padding:.75rem;background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.15);border-radius:8px;color:var(--pearl);font-family:'Outfit',sans-serif;font-size:.95rem;}
      input:focus{outline:none;border-color:var(--wave);background:rgba(11,61,94,.5);box-shadow:0 0 0 3px rgba(27,159,212,.12);}
      .btn{display:inline-block;padding:.8rem 1.5rem;background:var(--wave);color:var(--ink);border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:all .2s;font-size:.95rem;}
      .btn:hover{background:var(--foam);transform:translateY(-2px);box-shadow:0 8px 20px rgba(27,159,212,.25);}
      .btn-secondary{background:rgba(114,215,240,.1);border:1px solid rgba(114,215,240,.2);color:var(--foam);}
      .btn-secondary:hover{background:rgba(114,215,240,.2);transform:none;box-shadow:none;}
      .btn-back{margin-top:1rem;}
      .form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
      @media(max-width:600px){.form-row{grid-template-columns:1fr;}}
      a{color:var(--wave);text-decoration:none;transition:color .2s;}
      a:hover{color:var(--foam);}
    </style>
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