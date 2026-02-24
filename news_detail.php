<?php
require 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: news.php"); exit(); }

$stmt = $connessione->prepare("
    SELECT n.*, u.nome AS nome_autore, u.cognome AS cognome_autore, r.qualifica
    FROM news n
    JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
    JOIN utente u ON r.id_ricercatore = u.id_utente
    WHERE n.id_news = ?
");
$stmt->execute([$id]);
$n = $stmt->fetch();
if (!$n) { header("Location: news.php"); exit(); }

// Incrementa visualizzazioni
$connessione->prepare("UPDATE news SET visualizzazioni = visualizzazioni + 1 WHERE id_news = ?")->execute([$id]);

// Altre news dello stesso ricercatore
$altri = $connessione->prepare("
    SELECT id_news, titolo, data_pub FROM news
    WHERE id_ricercatore = ? AND id_news != ?
    ORDER BY data_pub DESC LIMIT 4
");
$altri->execute([$n['id_ricercatore'], $id]);
$altre_news = $altri->fetchAll();

$data = $n['data_pub'] ? date('d M Y', strtotime($n['data_pub'])) : '';
$autore = trim(($n['nome_autore'] ?? '') . ' ' . ($n['cognome_autore'] ?? ''));
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($n['titolo']) ?> — NetSea</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;--text:#c5e4f5;--muted:#5d9ab8;--kelp:#2cb89b;}
    body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);cursor:none;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}
    nav{position:sticky;top:0;z-index:200;height:64px;display:flex;align-items:center;padding:0 2.5rem;background:rgba(4,17,30,.95);border-bottom:1px solid rgba(114,215,240,.08);}
    .nav-logo{display:flex;align-items:center;gap:.6rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.6rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:32px;height:32px;}
    .nav-back{margin-left:auto;color:var(--muted);text-decoration:none;font-size:.875rem;transition:color .2s;}
    .nav-back:hover{color:var(--foam);}

    /* COPERTINA */
    .cover{width:100%;height:420px;position:relative;overflow:hidden;background:linear-gradient(135deg,var(--ocean),var(--deep));}
    .cover img,.cover video{width:100%;height:100%;object-fit:cover;display:block;}
    .cover-overlay{position:absolute;inset:0;background:linear-gradient(0deg,rgba(4,17,30,.85) 0%,rgba(4,17,30,.2) 60%,transparent 100%);}
    .cover-emoji{position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-end;padding-right:15%;font-size:10rem;opacity:.12;pointer-events:none;}

    /* ARTICOLO */
    .article-wrap{max-width:780px;margin:0 auto;padding:3rem 2rem 5rem;}
    .article-meta{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;margin-bottom:1.5rem;}
    .meta-tag{padding:.25rem .75rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;background:rgba(27,159,212,.15);border:1px solid rgba(27,159,212,.3);color:var(--foam);}
    .meta-date{color:var(--muted);font-size:.82rem;}
    .meta-views{color:var(--muted);font-size:.82rem;}
    h1{font-family:'Cormorant Garamond',serif;font-size:clamp(1.8rem,4vw,2.8rem);color:var(--pearl);font-weight:400;line-height:1.2;margin-bottom:1rem;}
    .autore-box{display:flex;align-items:center;gap:.75rem;padding:1rem;background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.1);border-radius:10px;margin-bottom:2rem;}
    .autore-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--wave),var(--kelp));display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
    .autore-info p{color:var(--pearl);font-size:.9rem;font-weight:500;}
    .autore-info span{color:var(--muted);font-size:.78rem;}

    /* TESTO ARTICOLO */
    .article-body{font-size:1rem;line-height:1.9;color:var(--text);}
    .article-body p{margin-bottom:1.25rem;}

    /* COPERTINA MEDIA inline */
    .media-cover{width:100%;border-radius:12px;overflow:hidden;margin-bottom:2rem;border:1px solid rgba(114,215,240,.12);}
    .media-cover img,.media-cover video{width:100%;max-height:400px;object-fit:cover;display:block;}

    /* ALTRE NEWS */
    .altre-news{margin-top:3rem;padding-top:2rem;border-top:1px solid rgba(114,215,240,.1);}
    .altre-title{font-family:'Cormorant Garamond',serif;font-size:1.4rem;color:var(--pearl);font-weight:400;margin-bottom:1rem;}
    .news-row{display:flex;align-items:center;gap:1rem;background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.07);border-radius:10px;padding:.9rem 1.1rem;margin-bottom:.6rem;text-decoration:none;transition:border-color .2s,background .2s;}
    .news-row:hover{border-color:rgba(114,215,240,.22);background:rgba(11,61,94,.4);}
    .news-row h4{color:var(--pearl);font-size:.88rem;font-weight:500;}
    .news-row .data{margin-left:auto;flex-shrink:0;font-size:.74rem;color:var(--muted);}

    @media(max-width:600px){.cover{height:260px;}.article-wrap{padding:2rem 1.25rem 3rem;}}
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo">
    <svg viewBox="0 0 40 40" fill="none">
      <circle cx="20" cy="20" r="18" fill="rgba(27,159,212,.15)" stroke="rgba(114,215,240,.3)" stroke-width="1"/>
      <path d="M8 22 Q12 16 16 22 Q20 28 24 22 Q28 16 32 22" stroke="#72d7f0" stroke-width="2" fill="none" stroke-linecap="round"/>
    </svg>
    NetSea
  </a>
  <a href="news.php" class="nav-back">← Tutte le news</a>
</nav>

<!-- COPERTINA -->
<div class="cover">
  <?php if (!empty($n['copertina'])): ?>
    <?php if (preg_match('/\.(mp4|webm)$/i', $n['copertina'])): ?>
      <video src="<?= htmlspecialchars($n['copertina']) ?>" autoplay muted loop playsinline></video>
    <?php else: ?>
      <img src="<?= htmlspecialchars($n['copertina']) ?>" alt="<?= htmlspecialchars($n['titolo']) ?>">
    <?php endif; ?>
  <?php else: ?>
    <div class="cover-emoji">🌊</div>
  <?php endif; ?>
  <div class="cover-overlay"></div>
</div>

<div class="article-wrap">
  <div class="article-meta">
    <span class="meta-tag">📰 News</span>
    <span class="meta-date">📅 <?= $data ?></span>
    <span class="meta-views">👁 <?= (int)($n['visualizzazioni'] ?? 0) ?> visualizzazioni</span>
  </div>

  <h1><?= htmlspecialchars($n['titolo']) ?></h1>

  <div class="autore-box">
    <div class="autore-avatar">🔬</div>
    <div class="autore-info">
      <p><?= htmlspecialchars($autore) ?></p>
      <span><?= htmlspecialchars($n['qualifica'] ?? 'Ricercatore') ?></span>
    </div>
  </div>

  <div class="article-body">
    <?php
    // Rende il testo con paragrafi e supporto **grassetto** e _corsivo_
    $testo = htmlspecialchars($n['contenuto'] ?? '');
    $testo = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $testo);
    $testo = preg_replace('/_(.+?)_/s', '<em>$1</em>', $testo);
    $paragrafi = explode("\n\n", $testo);
    foreach ($paragrafi as $p_txt) {
        $p_txt = trim($p_txt);
        if ($p_txt === '') continue;
        if ($p_txt === '---') { echo '<hr style="border:none;border-top:1px solid rgba(114,215,240,.15);margin:1.5rem 0;">'; continue; }
        echo '<p>' . nl2br($p_txt) . '</p>';
    }
    ?>
  </div>

  <!-- ALTRE NEWS -->
  <?php if (!empty($altre_news)): ?>
  <div class="altre-news">
    <p class="altre-title">Altri articoli dello stesso ricercatore</p>
    <?php foreach ($altre_news as $an): ?>
    <a href="news_detail.php?id=<?= $an['id_news'] ?>" class="news-row">
      <span style="font-size:1.4rem;">📄</span>
      <h4><?= htmlspecialchars($an['titolo']) ?></h4>
      <span class="data"><?= $an['data_pub'] ? date('d M Y', strtotime($an['data_pub'])) : '' ?></span>
    </a>
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