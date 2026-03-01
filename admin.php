<?php
require 'config.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.php'); exit();
}

// AZIONE RIFIUTA
if (isset($_GET['rifiuta'])) {
    $id_req = (int)$_GET['rifiuta'];
    $connessione->prepare("UPDATE Richiesta_Ricercatore SET stato = 'rifiutato' WHERE id_richiesta = ?")->execute([$id_req]);
    header("Location: admin.php?msg=rifiutato"); exit();
}

// AZIONE APPROVA
if (isset($_GET['approva'])) {
    $id_req = (int)$_GET['approva'];
    $stmt = $connessione->prepare("SELECT id_utente, qualifica_dichiarata FROM Richiesta_Ricercatore WHERE id_richiesta = ?");
    $stmt->execute([$id_req]);
    $req = $stmt->fetch();
    if ($req) {
        try {
            $connessione->beginTransaction();
            $check = $connessione->prepare("SELECT 1 FROM Ricercatore WHERE id_ricercatore = ?");
            $check->execute([$req['id_utente']]);
            if (!$check->fetch()) {
                $connessione->prepare("INSERT INTO Ricercatore (id_ricercatore, qualifica) VALUES (?, ?)")
                    ->execute([$req['id_utente'], $req['qualifica_dichiarata']]);
            }
            $connessione->prepare("UPDATE Richiesta_Ricercatore SET stato = 'approvato' WHERE id_richiesta = ?")
                ->execute([$id_req]);
            $connessione->commit();
            header("Location: admin.php?msg=approvato"); exit();
        } catch (Exception $e) {
            $connessione->rollBack();
            header("Location: admin.php?err=" . urlencode($e->getMessage())); exit();
        }
    }
}

// Richieste in attesa
$richieste = $connessione->query("
    SELECT r.*, u.nome, u.cognome
    FROM Richiesta_Ricercatore r
    JOIN Utente u ON r.id_utente = u.id_utente
    WHERE r.stato = 'in_attesa'
    ORDER BY r.id_richiesta DESC
")->fetchAll();

// Statistiche veloci
$tot_utenti   = $connessione->query("SELECT COUNT(*) FROM Utente")->fetchColumn();
$tot_ricercatori = $connessione->query("SELECT COUNT(*) FROM Ricercatore")->fetchColumn();
$tot_specie   = $connessione->query("SELECT COUNT(*) FROM specie")->fetchColumn();
$tot_news     = $connessione->query("SELECT COUNT(*) FROM news")->fetchColumn();
$tot_media    = $connessione->query("SELECT COUNT(*) FROM media")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pannello Admin — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .admin-wrap { max-width:1100px; margin:0 auto; padding:5.5rem 1.5rem 4rem; }
    .admin-hero { margin-bottom:2.5rem; }
    .admin-hero h1 { font-family:'Cormorant Garamond',serif; font-size:2.4rem; font-weight:400; color:var(--pearl); margin-bottom:.3rem; }
    .admin-hero p { color:var(--muted); font-size:.9rem; }

    /* Stats grid */
    .stats-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:1rem; margin-bottom:2.5rem; }
    .stat-card { background:rgba(11,61,94,.25); border:1px solid rgba(114,215,240,.1); border-radius:12px; padding:1.1rem 1.3rem; }
    .stat-label { font-size:.7rem; color:var(--muted); text-transform:uppercase; letter-spacing:.1em; margin-bottom:.35rem; }
    .stat-val { font-family:'Cormorant Garamond',serif; font-size:2rem; color:var(--pearl); font-weight:400; }

    /* Flash */
    .flash { padding:.75rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-size:.88rem; }
    .flash.ok  { background:rgba(44,184,155,.12); border:1px solid rgba(44,184,155,.3); color:#3dd4ae; }
    .flash.err { background:rgba(232,131,106,.1); border:1px solid rgba(232,131,106,.3); color:#e8836a; }

    /* Sezione richieste */
    .section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--wave); margin-bottom:1rem; display:flex; align-items:center; gap:.6rem; }
    .section-title span { background:rgba(27,159,212,.15); border:1px solid rgba(27,159,212,.25); color:var(--wave); padding:.1rem .6rem; border-radius:20px; }
    .req-list { display:flex; flex-direction:column; gap:1rem; }
    .req-card { background:rgba(11,61,94,.2); border:1px solid rgba(114,215,240,.1); border-radius:14px; padding:1.25rem 1.5rem; }
    .req-top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1rem; }
    .req-name { font-size:1rem; color:var(--pearl); font-weight:500; margin-bottom:.2rem; }
    .req-meta { font-size:.78rem; color:var(--muted); display:flex; flex-direction:column; gap:.15rem; }
    .req-meta span { color:var(--wave); }
    .req-files { display:flex; gap:.6rem; flex-wrap:wrap; margin-bottom:1rem; }
    .req-file-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .9rem; border-radius:7px; font-size:.78rem; text-decoration:none; transition:background .2s; }
    .req-file-btn.doc { background:rgba(27,159,212,.1); border:1px solid rgba(27,159,212,.2); color:var(--wave); }
    .req-file-btn.doc:hover { background:rgba(27,159,212,.22); }
    .req-file-btn.badge { background:rgba(44,184,155,.1); border:1px solid rgba(44,184,155,.2); color:#2cb89b; }
    .req-file-btn.badge:hover { background:rgba(44,184,155,.22); }
    .req-file-empty { font-size:.75rem; color:rgba(114,215,240,.3); padding:.4rem .75rem; border:1px solid rgba(114,215,240,.07); border-radius:7px; }
    .req-actions { display:flex; gap:.6rem; }
    .req-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1.25rem; border-radius:8px; font-size:.82rem; font-weight:600; text-decoration:none; transition:background .2s; }
    .req-btn.approve { background:rgba(44,184,155,.15); border:1px solid rgba(44,184,155,.3); color:#2cb89b; }
    .req-btn.approve:hover { background:rgba(44,184,155,.28); }
    .req-btn.reject { background:rgba(232,131,106,.1); border:1px solid rgba(232,131,106,.25); color:#e8836a; }
    .req-btn.reject:hover { background:rgba(232,131,106,.22); }
    .empty-req { color:var(--muted); padding:2rem; text-align:center; background:rgba(11,61,94,.15); border-radius:12px; border:1px solid rgba(114,215,240,.07); }
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo">
    <img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));">
  </a>
  <a href="javascript:history.back()" class="nav-back">← Indietro</a>
</nav>

<div class="admin-wrap">

  <div class="admin-hero">
    <h1>Pannello Admin</h1>
    <p>Gestione richieste ricercatori e statistiche piattaforma</p>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="flash ok">
    <?= $_GET['msg']==='approvato' ? '✓ Ricercatore approvato con successo.' : '✓ Richiesta rifiutata.' ?>
  </div>
  <?php endif; ?>
  <?php if (isset($_GET['err'])): ?>
  <div class="flash err">Errore: <?= htmlspecialchars($_GET['err']) ?></div>
  <?php endif; ?>

  <!-- STATISTICHE -->
  <div class="stats-grid">
    <div class="stat-card"><p class="stat-label">Utenti totali</p><p class="stat-val"><?= $tot_utenti ?></p></div>
    <div class="stat-card"><p class="stat-label">Ricercatori</p><p class="stat-val"><?= $tot_ricercatori ?></p></div>
    <div class="stat-card"><p class="stat-label">Specie</p><p class="stat-val"><?= $tot_specie ?></p></div>
    <div class="stat-card"><p class="stat-label">News</p><p class="stat-val"><?= $tot_news ?></p></div>
    <div class="stat-card"><p class="stat-label">Media feed</p><p class="stat-val"><?= $tot_media ?></p></div>
    <div class="stat-card" style="border-color:rgba(232,131,106,.2);">
      <p class="stat-label" style="color:#e8836a;">In attesa</p>
      <p class="stat-val" style="color:#e8836a;"><?= count($richieste) ?></p>
    </div>
  </div>

  <!-- RICHIESTE -->
  <p class="section-title">
    Richieste ricercatore
    <?php if(count($richieste)): ?><span><?= count($richieste) ?></span><?php endif; ?>
  </p>

  <?php if (empty($richieste)): ?>
    <div class="empty-req">Nessuna richiesta in attesa di approvazione.</div>
  <?php else: ?>
  <div class="req-list">
    <?php foreach($richieste as $r): ?>
    <div class="req-card">
      <div class="req-top">
        <div>
          <p class="req-name"><?= htmlspecialchars($r['nome'].' '.$r['cognome']) ?></p>
          <div class="req-meta">
            <span><?= htmlspecialchars($r['qualifica_dichiarata'] ?? 'N/D') ?></span>
            <span style="color:var(--muted);"><?= htmlspecialchars($r['ente_dichiarato'] ?? '') ?></span>
            <?php if(!empty($r['motivazione'])): ?>
            <span style="color:rgba(197,228,245,.6);font-style:italic;max-width:480px;">"<?= htmlspecialchars(mb_substr($r['motivazione'],0,180)) ?><?= mb_strlen($r['motivazione']??'')>180?'…':'' ?>"</span>
            <?php endif; ?>
          </div>
        </div>
        <span style="font-size:.68rem;color:rgba(114,215,240,.35);">#<?= $r['id_richiesta'] ?></span>
      </div>

      <div class="req-files">
        <?php if(!empty($r['certificato_path'])): ?>
          <a href="<?= htmlspecialchars($r['certificato_path']) ?>" target="_blank" class="req-file-btn doc">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Certificato
          </a>
        <?php else: ?><span class="req-file-empty">Nessun certificato</span><?php endif; ?>
        <?php if(!empty($r['badge_path'])): ?>
          <a href="<?= htmlspecialchars($r['badge_path']) ?>" target="_blank" class="req-file-btn badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
            Badge
          </a>
        <?php else: ?><span class="req-file-empty">Nessun badge</span><?php endif; ?>
      </div>

      <div class="req-actions">
        <a href="admin.php?approva=<?= $r['id_richiesta'] ?>"
           onclick="return confirm('Approvare <?= htmlspecialchars(addslashes($r['nome'].' '.$r['cognome'])) ?>?')"
           class="req-btn approve">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Approva
        </a>
        <a href="admin.php?rifiuta=<?= $r['id_richiesta'] ?>"
           onclick="return confirm('Rifiutare la richiesta di <?= htmlspecialchars(addslashes($r['nome'].' '.$r['cognome'])) ?>?')"
           class="req-btn reject">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          Rifiuta
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();
</script>
</body>
</html>