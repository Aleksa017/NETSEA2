<?php
require 'config.php';

$utente_loggato = isset($_SESSION['id']) ? [
    'nome'    => $_SESSION['nome']    ?? '',
    'cognome' => $_SESSION['cognome'] ?? '',
    'ruolo'   => $_SESSION['ruolo']   ?? 'utente',
] : null;
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NetSea — Ecosistemi Marini</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- CUSTOM CURSOR -->
<div class="cursor" id="cursor" style="opacity:0;"></div>
<div class="cursor-ring" id="cursorRing" style="opacity:0;"></div>

<!-- ══════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════ -->
<nav id="navbar" class="nav-index">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-wave">
      <svg viewBox="0 0 40 40" fill="none">
        <circle cx="20" cy="20" r="18" fill="rgba(27,159,212,.15)" stroke="rgba(114,215,240,.3)" stroke-width="1"/>
        <path d="M8 22 Q12 16 16 22 Q20 28 24 22 Q28 16 32 22" stroke="#72d7f0" stroke-width="2" fill="none" stroke-linecap="round"/>
        <path d="M8 18 Q12 12 16 18 Q20 24 24 18 Q28 12 32 18" stroke="rgba(114,215,240,.45)" stroke-width="1.5" fill="none" stroke-linecap="round"/>
      </svg>
    </div>
    NetSea
  </a>

  <ul class="nav-links">
    <li><a href="news.php">News</a></li>
    <li><a href="#donazioni">Donazioni</a></li>
    <li><a href="feed.php">Scopri</a></li>
    <li><a href="Specie.php">Specie</a></li>
    <li><a href="Luoghi.php">Luoghi</a></li>
  </ul>

  <!-- SEARCH -->
  <div class="nav-search">
    <svg class="icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/>
    </svg>
    <input type="text" id="searchInput" placeholder="Cerca specie, habitat, ricerche…" autocomplete="off">
    <button class="clear-btn" id="clearBtn" onclick="clearSearch()" style="display:none;">✕</button>
  </div>

  <div class="nav-actions">
    <!-- USER DROPDOWN TRIGGER -->
    <div class="user-btn-wrap" id="userBtnWrap">
      <button class="nav-icon-btn" id="userBtn" title="Accedi o registrati" onclick="toggleDropdown(event)">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
          <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
      </button>
      <?php if ($utente_loggato): ?>
      <div class="user-dropdown" id="userDropdown">
        <div class="user-drop-top">
          <div class="avatar">
            <?php
              if($utente_loggato['ruolo']==='admin') echo '⚙️';
              elseif($utente_loggato['ruolo']==='ricercatore') echo '🔬';
              else echo '👤';
            ?>
          </div>
          <strong><?= htmlspecialchars($utente_loggato['nome'].' '.$utente_loggato['cognome']) ?></strong>
          <p><?= htmlspecialchars(ucfirst($utente_loggato['ruolo'])) ?></p>
        </div>
        <div class="user-drop-actions">
    <a href="profilo.php" class="drop-link"><span class="icon">👤</span> Il mio profilo</a>
    
    <?php if(isset($_SESSION['ruolo']) && in_array($_SESSION['ruolo'], ['ricercatore', 'admin'])): ?>
        <a href="crea_news.php" class="drop-link"><span class="icon">📰</span> Pubblica news</a>
        <a href="crea_contenuto.php" class="drop-link"><span class="icon">✏️</span> Crea contenuto</a>
    <?php endif; ?>

    <?php if(isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin'): ?>
        <a href="admin.php" class="drop-link"><span class="icon">⚙️</span> Pannello Admin</a>
    <?php endif; ?>

    <div class="drop-divider"></div>
    <a href="logout.php" class="drop-btn drop-btn-ghost">🚪 Esci</a>
</div>
      </div>
      <?php else: ?>
      <div class="user-dropdown" id="userDropdown">
        <div class="user-drop-top">
          <div class="avatar">🌊</div>
          <strong>Benvenuto su NetSea</strong>
          <p>Accedi per feed personalizzato e donazioni</p>
        </div>
        <div class="user-drop-actions">
          <a href="Login.php" class="drop-btn drop-btn-primary">Accedi</a>
          <a href="Registrazione.php" class="drop-btn drop-btn-secondary">✨ Crea account</a>
          <div class="drop-divider"></div>
          <a href="Registrazione.php?tipo=ricercatore" class="drop-link"><span class="icon">🔬</span> Richiedi account ricercatore</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ══════════════════════════════════════════
     SEARCH OVERLAY
══════════════════════════════════════════ -->
<div class="search-overlay" id="searchOverlay">
  <button class="search-close" onclick="closeSearch()">✕</button>
  <div class="search-overlay-inner">
    <p class="search-query-label">Risultati per: <strong id="queryDisplay"></strong></p>
    <!-- Tutto viene generato dal JS -->
    <div id="searchResults"></div>
  </div>
</div>

<!-- ══════════════════════════════════════════
     HERO — NEWS CAROUSEL
══════════════════════════════════════════ -->
<?php
// Ultime 8 news dal DB
$grads = [
    'linear-gradient(135deg,#062040,#0a4060,#0b5575)',
    'linear-gradient(135deg,#031a10,#0a3d20,#0d5530)',
    'linear-gradient(135deg,#1a0f30,#2a1050,#1a0a40)',
    'linear-gradient(135deg,#002020,#004040,#005550)',
    'linear-gradient(135deg,#200a00,#401500,#502010)',
    'linear-gradient(135deg,#0a0a30,#101060,#0a0a50)',
    'linear-gradient(135deg,#062040,#0a4060,#0b5575)',
    'linear-gradient(135deg,#031a10,#0a3d20,#0d5530)',
];
$emojis = ['🌊','🪸','🦑','🐋','🧫','🔬','🐟','🌿'];
try {
    $stmt_news_car = $connessione->query("
        SELECT n.id_news, n.titolo, n.contenuto, n.copertina, n.data_pub,
               u.nome AS nome_autore, u.cognome AS cognome_autore, r.qualifica
        FROM news n
        JOIN ricercatore r ON n.id_ricercatore = r.id_ricercatore
        JOIN utente u ON r.id_ricercatore = u.id_utente
        ORDER BY n.data_pub DESC LIMIT 8
    ");
    $news_carousel = $stmt_news_car->fetchAll();
} catch (PDOException $e) { $news_carousel = []; }
?>
<section class="hero-carousel" id="news">
  <div class="carousel-track" id="carouselTrack">
  <?php if (empty($news_carousel)): ?>
    <div class="slide">
      <div class="slide-bg" style="background:linear-gradient(135deg,#062040,#0b5575);">
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-end;padding-right:20%;font-size:12rem;opacity:.15;user-select:none;">🌊</div>
      </div>
      <div class="slide-content">
        <span class="slide-tag">📰 News</span>
        <h1 class="slide-title">Benvenuto su NetSea</h1>
        <p class="slide-desc">Le news pubblicate dai ricercatori verificati appariranno qui.</p>
        <a href="news.php" class="slide-btn">Vai alle news →</a>
      </div>
    </div>
  <?php else: ?>
  <?php foreach ($news_carousel as $i => $nc):
    $grad  = $grads[$i % count($grads)];
    $emoji = $emojis[$i % count($emojis)];
    $autore_nc = trim(($nc['nome_autore'] ?? '') . ' ' . ($nc['cognome_autore'] ?? ''));
    $data_nc = $nc['data_pub'] ? date('d M Y', strtotime($nc['data_pub'])) : '';
    $desc_nc = mb_substr(strip_tags($nc['contenuto'] ?? ''), 0, 160);
    $hasCover = !empty($nc['copertina']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $nc['copertina']);
  ?>
  <div class="slide">
    <div class="slide-bg" style="background:<?= $grad ?>;">
      <?php if ($hasCover): ?>
        <img src="<?= htmlspecialchars($nc['copertina']) ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.4;">
      <?php endif; ?>
      <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-end;padding-right:20%;font-size:12rem;opacity:.12;user-select:none;"><?= $emoji ?></div>
    </div>
    <div class="slide-content">
      <span class="slide-tag">📰 News</span>
      <h1 class="slide-title"><?= htmlspecialchars($nc['titolo']) ?></h1>
      <p class="slide-desc"><?= htmlspecialchars($desc_nc) ?>…</p>
      <p class="slide-author">Di <span><?= htmlspecialchars($autore_nc) ?></span><?= $nc['qualifica'] ? ' · ' . htmlspecialchars($nc['qualifica']) : '' ?><?= $data_nc ? ' · ' . $data_nc : '' ?></p>
      <a href="news_detail.php?id=<?= $nc['id_news'] ?>" class="slide-btn">Leggi l'articolo →</a>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
  </div>

  <!-- STRIP THUMBNAILS RIGHT -->
  <div class="slide-strip" id="slideStrip">
    <?php
    $emojis_strip = ['🌊','🪸','🦑','🐋','🧫','🔬','🐟','🌿'];
    $tot = max(1, count($news_carousel));
    $max_strip = min($tot, 8);
    for ($i = 0; $i < $max_strip; $i++):
    ?>
    <div class="strip-thumb <?= $i===0?'active':'' ?>" data-idx="<?= $i ?>">
      <?= $emojis_strip[$i % count($emojis_strip)] ?>
    </div>
    <?php endfor; ?>
    <!-- link alle news completo -->
    <a href="news.php" style="display:flex;align-items:center;justify-content:center;margin-top:.5rem;padding:.4rem .7rem;border-radius:8px;background:rgba(27,159,212,.15);border:1px solid rgba(27,159,212,.3);color:#72d7f0;font-size:.72rem;font-family:'Outfit',sans-serif;text-decoration:none;gap:.3rem;transition:background .2s;" onmouseover="this.style.background='rgba(27,159,212,.3)'" onmouseout="this.style.background='rgba(27,159,212,.15)'">
      Tutte →
    </a>
  </div>

  <!-- ARROWS -->
  <button class="carousel-arrow prev" onclick="moveTo(currentSlide - 1)">‹</button>
  <button class="carousel-arrow next" onclick="moveTo(currentSlide + 1)">›</button>

  <!-- DOTS -->
  <div class="carousel-dots" id="carouselDots"></div>
</section>

<!-- STATS BAR -->
<div class="stats-row">
  <!-- PHP: query counts from DB -->
  <div class="stat-item anim"><span class="stat-num">2.400+</span><span class="stat-lbl">Specie Monitorate</span></div>
  <div class="stat-item anim anim-d1"><span class="stat-num">380</span><span class="stat-lbl">A Rischio</span></div>
  <div class="stat-item anim anim-d2"><span class="stat-num">48</span><span class="stat-lbl">Enti di Ricerca</span></div>
  <div class="stat-item anim anim-d3"><span class="stat-num">12k+</span><span class="stat-lbl">Utenti Attivi</span></div>
</div>

<main>
  <!-- ══════════════════════════════════════════
       DONAZIONI
  ══════════════════════════════════════════ -->
  <?php
  // Ultimi 10 progetti, raccolto dal campo diretto
  try {
      $stmt_don = $connessione->query("SELECT * FROM progetto ORDER BY data_i DESC LIMIT 10");
      $progetti = $stmt_don->fetchAll();
  } catch (PDOException $e) { $progetti = []; }
  ?>
  <section class="donations-section" id="donazioni">
    <div class="container">
      <div class="donations-header">
        <div>
          <p class="section-eyebrow">💚 Supporta la Ricerca</p>
          <h2 class="section-title">Progetti di Donazione</h2>
          <p class="section-sub">Finanzia direttamente la scienza che protegge i nostri oceani</p>
        </div>
        <a href="progetti.php" class="btn-outline" style="align-self:flex-start;">Vedi tutti →</a>
      </div>

      <div class="donation-grid" id="donationGrid">
        <?php if (empty($progetti)): ?>
          <p style="color:var(--muted);padding:2rem;">Nessun progetto disponibile.</p>
        <?php else: ?>
        <?php
        $stati_icons = ['attivo'=>'🟢','urgente'=>'🔴','completato'=>'✅'];
        $top_grads = [
            'urgente'   => 'background:linear-gradient(135deg,var(--ocean),var(--deep));',
            'completato'=> 'background:linear-gradient(135deg,var(--ocean),var(--deep));',
            'attivo'    => 'background:linear-gradient(135deg,var(--ocean),var(--deep));',
        ];
        $emojis_don = ['🌊','🪸','🦑','🐋','🧫','🔬','🐟','🌿','🐠','🐢'];
        $badge_styles = [
            'urgente'   => 'background:rgba(224,90,58,.2);color:#e8836a;border-color:rgba(224,90,58,.3);',
            'completato'=> 'background:rgba(44,184,155,.15);color:#3dd4ae;border-color:rgba(44,184,155,.3);',
            'attivo'    => 'background:rgba(44,184,155,.2);color:var(--kelp);border-color:rgba(44,184,155,.3);',
        ];
        foreach($progetti as $don_i => $p):
            $stato    = strtolower($p['stato'] ?? 'attivo');
            $raccolto = (float)($p['raccolto'] ?? 0);
            $budget   = (float)($p['budget']   ?? 0);
            $pct      = $budget > 0 ? min(100, round($raccolto / $budget * 100)) : 0;
            $s_icon   = $stati_icons[$stato] ?? '🟢';
            $top_grad = $top_grads[$stato] ?? $top_grads['attivo'];
            $badge_s  = $badge_styles[$stato] ?? $badge_styles['attivo'];
            $label    = ucfirst($stato);
        ?>
        <a href="progetto_detail.php?id=<?= $p['id_pd'] ?>" class="donation-card" data-stato="<?= htmlspecialchars($stato) ?>" style="display:block;text-decoration:none;">
          <div class="donation-card-top" style="<?= $top_grad ?>">
            <span style="font-size:4rem;"><?= $emojis_don[$don_i % count($emojis_don)] ?></span>
            <span class="donation-status" style="<?= $badge_s ?>"><?= $s_icon ?> <?= $label ?></span>
          </div>
          <div class="donation-card-body">
            <h3><?= htmlspecialchars($p['titolo']) ?></h3>
            <p><?= htmlspecialchars(mb_substr($p['obiettivo'] ?? '', 0, 100)) . (mb_strlen($p['obiettivo'] ?? '') > 100 ? '…' : '') ?></p>
            <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
            <div class="progress-meta">
              <span><strong>€ <?= number_format($raccolto,0,',','.') ?></strong> raccolti</span>
              <span>di € <?= number_format($budget,0,',','.') ?></span>
            </div>
            <span class="btn-solid" style="font-size:.82rem;padding:.5rem 1rem;display:inline-block;">💚 Scopri e dona</span>
          </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
      </div>

      <div style="text-align:center;margin-top:1.75rem;">
        <a href="progetti.php" class="btn-outline">Vedi tutti i progetti →</a>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       FEED — TikTok style
  ══════════════════════════════════════════ -->
  <?php
  // Ultimi 8 contenuti dal DB
  try {
      $feed_preview = $connessione->query("
          SELECT m.*, u.nome, u.cognome,
                 (SELECT COUNT(*) FROM like_media l WHERE l.id_post = m.id_post) AS like_count
          FROM media m
          LEFT JOIN utente u ON m.id_utente = u.id_utente
          ORDER BY m.data_pub DESC LIMIT 8
      ")->fetchAll();
  } catch (PDOException $e) { $feed_preview = []; }
  $grads_feed = ['linear-gradient(135deg,#041828,#0b3d5e)','linear-gradient(135deg,#002820,#005540)','linear-gradient(135deg,#200a20,#401040)','linear-gradient(135deg,#001830,#003060)','linear-gradient(135deg,#201000,#402000)','linear-gradient(135deg,#000820,#001540)','linear-gradient(135deg,#002010,#003020)','linear-gradient(135deg,#1a0f30,#2a1050)'];
  $emojis_feed = ['🦈','🪸','🐙','🐬','🐠','🌊','🐢','🦑'];
  ?>
  <section class="feed-section" id="feed">
    <div class="container">
      <div class="feed-header">
        <div>
          <p class="section-eyebrow">✨ Scopri</p>
          <h2 class="section-title">Feed Marino</h2>
          <p class="section-sub">Contenuti dei ricercatori verificati — video, foto, scoperte</p>
        </div>
        <a href="feed.php" class="btn-outline">Vai al feed completo →</a>
      </div>

      <div class="feed-scroll-track">
      <?php if (empty($feed_preview)): ?>
        <div class="feed-card" style="cursor:default;">
          <div class="feed-card-bg">🌊</div>
          <div class="feed-card-overlay">
            <p class="feed-card-title">Nessun contenuto ancora</p>
          </div>
        </div>
      <?php else: ?>
      <?php foreach ($feed_preview as $fi => $f):
        $isImg_f = !empty($f['url']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f['url']);
        $isVid_f = !empty($f['url']) && preg_match('/\.(mp4|webm)$/i', $f['url']);
        $autore_f = trim(($f['nome']??'').' '.($f['cognome']??'')) ?: 'NetSea';
        $grad_f   = $grads_feed[$fi % 8];
        $emoji_f  = $emojis_feed[$fi % 8];
        $tipo_f   = $isVid_f ? '📹 Video' : '📸 Foto';
      ?>
      <div class="feed-card"
           style="cursor:pointer;"
           data-id="<?= $f['id_post'] ?>"
           data-titolo="<?= htmlspecialchars($f['titolo'], ENT_QUOTES) ?>"
           data-desc="<?= htmlspecialchars($f['descrizione'] ?? '', ENT_QUOTES) ?>"
           data-autore="<?= htmlspecialchars($autore_f, ENT_QUOTES) ?>"
           data-url="<?= htmlspecialchars($f['url'] ?? '', ENT_QUOTES) ?>"
           data-likes="<?= (int)$f['like_count'] ?>"
           data-liked="0"
           onclick="apriModalIndex(this)">
        <div class="feed-card-bg" style="background:<?= $grad_f ?>;">
          <?php if ($isImg_f): ?>
            <img src="<?= htmlspecialchars($f['url']) ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.75;border-radius:0;">
          <?php else: ?><?= $emoji_f ?><?php endif; ?>
        </div>
        <?php if ($isVid_f): ?><div class="feed-card-play">▶</div><?php endif; ?>
        <div class="feed-card-overlay">
          <p class="feed-card-type"><?= $tipo_f ?></p>
          <p class="feed-card-title"><?= htmlspecialchars(mb_substr($f['titolo'],0,55)) ?></p>
          <p class="feed-card-author"><?= htmlspecialchars($autore_f) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- MODAL per le card del feed in index -->
  <div id="indexModalOverlay" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(4,17,30,.92);backdrop-filter:blur(12px);align-items:center;justify-content:center;" onclick="chiudiIndexModal(event)">
    <div style="background:rgba(11,61,94,.35);border:1px solid rgba(114,215,240,.2);border-radius:20px;max-width:660px;width:92%;max-height:88vh;overflow-y:auto;position:relative;">
      <button onclick="chiudiIndexModalBtn()" style="position:absolute;top:1rem;right:1rem;width:36px;height:36px;border-radius:50%;background:rgba(4,17,30,.7);border:1px solid rgba(114,215,240,.2);color:#fff;font-size:1.1rem;cursor:pointer;z-index:10;display:flex;align-items:center;justify-content:center;">✕</button>
      <div id="indexModalMedia"></div>
      <div style="padding:1.5rem;">
        <p id="indexModalTipo" style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#72d7f0;margin-bottom:.5rem;"></p>
        <h2 id="indexModalTitolo" style="font-family:'Cormorant Garamond',serif;font-size:1.7rem;color:#e8f6fc;font-weight:400;line-height:1.2;margin-bottom:.6rem;"></h2>
        <p id="indexModalAutore" style="color:#5d9ab8;font-size:.82rem;margin-bottom:1rem;"></p>
        <p id="indexModalDesc" style="color:#c5e4f5;font-size:.9rem;line-height:1.75;margin-bottom:1.5rem;"></p>
        <div style="display:flex;gap:1rem;align-items:center;">
          <button id="indexModalLikeBtn" onclick="toggleLikeIndex()" style="display:flex;align-items:center;gap:.5rem;padding:.65rem 1.4rem;border-radius:50px;border:1px solid rgba(114,215,240,.2);background:rgba(11,61,94,.4);color:#c5e4f5;font-family:'Outfit',sans-serif;font-size:.9rem;cursor:pointer;transition:all .2s;">
            <span id="indexModalLikeIcon">🤍</span> <span id="indexModalLikeCount">0</span> Mi piace
          </button>
          <a href="feed.php" style="color:#5d9ab8;font-size:.82rem;text-decoration:none;">Vai al feed completo →</a>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ══════════════════════════════════════════
     FOOTER WAVE TRANSITION → SAND
══════════════════════════════════════════ -->
<div class="wave-sep">
  <svg viewBox="0 0 1440 120" preserveAspectRatio="none" style="width:100%;height:120px;display:block;">
    <path d="M0,40 C180,80 360,0 540,40 C720,80 900,10 1080,40 C1260,70 1380,20 1440,40 L1440,120 L0,120 Z"
          fill="rgba(27,159,212,.2)"/>
    <path d="M0,60 C240,20 480,80 720,50 C960,20 1200,70 1440,50 L1440,120 L0,120 Z"
          fill="rgba(114,215,240,.12)"/>
    <path d="M0,80 C300,50 600,100 900,70 C1100,50 1300,85 1440,80 L1440,120 L0,120 Z"
          fill="#f5ede0"/>
  </svg>
</div>

<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <span class="logo">🌊 NetSea</span>
        <p>Piattaforma dedicata alla ricerca e alla divulgazione sugli ecosistemi marini. Dati aggiornati, specie monitorate, news dalla scienza oceanografica.</p>
        <div style="margin-top:1.25rem;display:flex;gap:.75rem;">
          <a href="#" style="color:#7a6a58;font-size:1.2rem;text-decoration:none;">𝕏</a>
          <a href="#" style="color:#7a6a58;font-size:1.2rem;text-decoration:none;">📷</a>
          <a href="#" style="color:#7a6a58;font-size:1.2rem;text-decoration:none;">▶</a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Esplora</h4>
        <ul>
          <li><a href="specie.php">Specie Marine</a></li>
          <li><a href="luoghi.php">Luoghi & Habitat</a></li>
          <li><a href="news.php">News Scientifiche</a></li>
          <li><a href="progetti.php">Donazioni</a></li>
          <li><a href="feed.php">Feed Scoperte</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Ricerca</h4>
        <ul>
          <li><a href="#">Rilevazioni Ambientali</a></li>
          <li><a href="#">Enti di Ricerca</a></li>
          <li><a href="#">Pubblicazioni</a></li>
          <li><a href="Registrazione.php">Diventa Ricercatore</a></li>
          <li><a href="Login.php">Area Ricercatori</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Info</h4>
        <ul>
          <li><a href="#">Chi siamo</a></li>
          <li><a href="#">Metodologia</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Termini d'uso</a></li>
          <li><a href="#">Contatti</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 NetSea · Tutti i diritti riservati · Progetto scolastico ITIS</p>
      <p style="font-size:.75rem;color:#b09a80;">🐚 &nbsp; 🦀 &nbsp; 🌿</p>
    </div>
  </div>
</footer>

<script>
/* ══════════════════════════════════════════
   CURSOR
══════════════════════════════════════════ */
const cursor = document.getElementById('cursor');
const cursorRing = document.getElementById('cursorRing');
let mx = 0, my = 0, rx = 0, ry = 0;
document.addEventListener('mousemove', e => {
  mx = e.clientX; my = e.clientY;
  cursor.style.left = mx + 'px';
  cursor.style.top = my + 'px';
  cursor.style.opacity = '1';
  cursorRing.style.opacity = '1';
}, {once: false});
// Mostra cursore al primo movimento
document.addEventListener('mousemove', () => {
  cursor.style.transition = 'opacity .3s';
  cursorRing.style.transition = 'opacity .3s';
}, {once: true});
function animRing() {
  rx += (mx - rx) * .12;
  ry += (my - ry) * .12;
  cursorRing.style.left = rx + 'px';
  cursorRing.style.top = ry + 'px';
  requestAnimationFrame(animRing);
}
animRing();

/* ══════════════════════════════════════════
   USER DROPDOWN
══════════════════════════════════════════ */
function toggleDropdown(e) {
  e.stopPropagation();
  const drop = document.getElementById('userDropdown');
  if (!drop) return;
  drop.classList.toggle('open');
}

function closeAllDropdowns() {
  document.querySelectorAll('.user-dropdown').forEach(d => d.classList.remove('open'));
}

document.addEventListener('click', () => closeAllDropdowns());

/* ══════════════════════════════════════════
   NAVBAR SCROLL
══════════════════════════════════════════ */
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 40);
});

/* ══════════════════════════════════════════
   CAROUSEL
══════════════════════════════════════════ */
const track = document.getElementById('carouselTrack');
const dotsContainer = document.getElementById('carouselDots');
const stripThumbs = document.querySelectorAll('.strip-thumb');
const slides = document.querySelectorAll('.slide');
const totalSlides = slides.length;
let currentSlide = 0;
let autoTimer;

// Build dots
slides.forEach((_, i) => {
  const d = document.createElement('button');
  d.className = 'dot' + (i === 0 ? ' active' : '');
  d.onclick = () => moveTo(i);
  dotsContainer.appendChild(d);
});

function moveTo(idx) {
  currentSlide = ((idx % totalSlides) + totalSlides) % totalSlides;
  track.style.transform = `translateX(-${currentSlide * 100}%)`;
  // dots
  document.querySelectorAll('.dot').forEach((d, i) => d.classList.toggle('active', i === currentSlide));
  // strip: aggiorna active e scrolla per rendere visibile il thumb corrente
  stripThumbs.forEach((t, i) => {
    const isActive = i === currentSlide;
    t.classList.toggle('active', isActive);
    if (isActive) {
      // Scrolla la strip verticalmente per portare il thumb attivo al centro
      const strip = document.getElementById('slideStrip');
      const thumbTop = t.offsetTop;
      const thumbH = t.offsetHeight;
      const stripH = strip.offsetHeight;
      const target = thumbTop - (stripH / 2) + (thumbH / 2);
      strip.scrollTo({ top: target, behavior: 'smooth' });
    }
  });
  resetAuto();
}

function resetAuto() {
  clearInterval(autoTimer);
  autoTimer = setInterval(() => moveTo(currentSlide + 1), 6000);
}

// Strip clicks
stripThumbs.forEach(t => t.addEventListener('click', () => moveTo(+t.dataset.idx)));

// Keyboard
document.addEventListener('keydown', e => {
  if (document.getElementById('searchOverlay').classList.contains('active')) return;
  if (e.key === 'ArrowLeft') moveTo(currentSlide - 1);
  if (e.key === 'ArrowRight') moveTo(currentSlide + 1);
});

// Touch swipe
let touchStartX = 0;
track.addEventListener('touchstart', e => touchStartX = e.touches[0].clientX);
track.addEventListener('touchend', e => {
  const dx = e.changedTouches[0].clientX - touchStartX;
  if (Math.abs(dx) > 50) moveTo(currentSlide + (dx < 0 ? 1 : -1));
});

resetAuto();

/* ══════════════════════════════════════════
   SEARCH
══════════════════════════════════════════ */
// ── RICERCA REALE DAL DATABASE ──────────────────────────────────────────
let searchTimer = null;
let lastQuery = '';

function clearSearch() {
  const inp = document.getElementById('searchInput');
  inp.value = '';
  document.getElementById('clearBtn').style.display = 'none';
  closeSearch();
  inp.focus();
}
function closeSearch() {
  const ov = document.getElementById('searchOverlay');
  if (ov) ov.classList.remove('active');
  document.body.style.overflow = '';
}

const searchInput = document.getElementById('searchInput');
const overlay = document.getElementById('searchOverlay');
const clearBtn = document.getElementById('clearBtn');

searchInput.addEventListener('input', e => {
  const q = e.target.value.trim();
  clearBtn.style.display = q ? 'block' : 'none';
  if (q.length >= 2) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => cercaNelDB(q), 350);
  } else {
    closeSearch();
  }
});

searchInput.addEventListener('keydown', e => {
  if (e.key === 'Escape') { clearSearch(); closeSearch(); inp && inp.blur(); }
  if (e.key === 'Enter') {
    const q = searchInput.value.trim();
    if (q.length >= 2) { clearTimeout(searchTimer); cercaNelDB(q); }
  }
});

async function cercaNelDB(query) {
  lastQuery = query;
  overlay.classList.add('active');
  document.getElementById('queryDisplay').textContent = query;
  document.getElementById('searchResults').innerHTML = '<p style="color:var(--muted);padding:2rem 0;">Ricerca in corso…</p>';

  try {
    const res = await fetch('api/cerca.php?q=' + encodeURIComponent(query));
    const dati = await res.json();
    if (query !== lastQuery) return;
    renderRisultati(dati, query);
  } catch(err) {
    document.getElementById('searchResults').innerHTML = '<p style="color:#e8836a;">Errore durante la ricerca.</p>';
  }
}

function renderRisultati(dati, query) {
  const box = document.getElementById('searchResults');
  let html = '';

  // ── SCHEDA SPECIE ─────────────────────────────────────────────────────
  if (dati.specie) {
    const s = dati.specie;
    const stato = (s.stato_conservazione || '').toUpperCase();
    const colori = {CR:'#e8836a',EN:'#e0a060',VU:'#f0c040',NT:'#c8a830',LC:'#2cb89b',DD:'#5d9ab8'};
    const col = colori[stato] || '#5d9ab8';
    const imgHtml = s.immagine
      ? `<img src="${esc(s.immagine)}" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">`
      : '🐟';

    html += `
    <div style="
      background:linear-gradient(135deg,rgba(11,61,94,.6),rgba(7,30,51,.8));
      border:1px solid rgba(114,215,240,.2);border-radius:16px;
      padding:1.5rem;display:grid;grid-template-columns:1fr 150px;
      gap:1.5rem;align-items:center;margin-bottom:1.5rem;
    ">
      <div>
        <span style="display:inline-block;padding:.2rem .75rem;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;border:1px solid ${col}44;background:${col}18;color:${col};margin-bottom:.75rem;">
          📌 ${esc(stato)} — Specie trovata
        </span>
        <h2 style="font-family:'Cormorant Garamond',serif;font-size:1.8rem;color:#e8f6fc;margin-bottom:.2rem;">${esc(s.nome)}</h2>
        <p style="font-style:italic;color:#5d9ab8;font-size:.9rem;margin-bottom:.75rem;">${esc(s.nome_scientifico||'')}</p>
        <p style="color:#c5e4f5;font-size:.88rem;line-height:1.65;margin-bottom:1rem;">${esc((s.descrizione||'').slice(0,200))}${(s.descrizione||'').length>200?'…':''}</p>
        <a href="specie.php?id=${s.id_specie}" style="display:inline-flex;align-items:center;gap:.5rem;padding:.55rem 1.25rem;background:#1b9fd4;color:#04111e;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem;">
          Scheda completa →
        </a>
      </div>
      <div style="width:150px;height:150px;border-radius:12px;overflow:hidden;background:linear-gradient(135deg,#0b3d5e,#071e33);border:1px solid rgba(114,215,240,.15);display:flex;align-items:center;justify-content:center;font-size:5rem;flex-shrink:0;">
        ${imgHtml}
      </div>
    </div>`;
  }

  // ── ALTRE SPECIE ──────────────────────────────────────────────────────
  const altreSpecie = (dati.specie_lista||[]).filter(s => !dati.specie || s.id_specie != dati.specie.id_specie);
  if (altreSpecie.length) {
    html += `<p style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#5d9ab8;margin-bottom:.75rem;">🐠 Altre specie correlate</p>`;
    altreSpecie.forEach(s => {
      const stato = (s.stato_conservazione||'').toUpperCase();
      const colori = {CR:'#e8836a',EN:'#e0a060',VU:'#f0c040',LC:'#2cb89b'};
      const col = colori[stato]||'#5d9ab8';
      html += `
      <a href="specie.php?id=${s.id_specie}" style="display:flex;align-items:center;gap:1rem;background:rgba(11,61,94,.25);border:1px solid rgba(114,215,240,.08);border-radius:10px;padding:.9rem 1.1rem;margin-bottom:.6rem;text-decoration:none;transition:border-color .2s;" onmouseover="this.style.borderColor='rgba(114,215,240,.25)'" onmouseout="this.style.borderColor='rgba(114,215,240,.08)'">
        <span style="font-size:1.8rem;">🐟</span>
        <div style="flex:1;">
          <p style="color:#e8f6fc;font-size:.9rem;font-weight:500;">${esc(s.nome)}</p>
          <p style="color:#5d9ab8;font-size:.78rem;font-style:italic;">${esc(s.nome_scientifico||'')}</p>
        </div>
        <span style="padding:.2rem .65rem;border-radius:20px;font-size:.68rem;font-weight:700;border:1px solid ${col}44;background:${col}18;color:${col};">${esc(stato)}</span>
      </a>`;
    });
    html += '<div style="height:1rem;"></div>';
  }

  // ── NEWS ──────────────────────────────────────────────────────────────
  if (dati.news && dati.news.length) {
    html += `<p style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#5d9ab8;margin-bottom:.75rem;">📰 News correlate</p>`;
    dati.news.forEach(n => {
      html += `
      <a href="news_detail.php?id=${n.id_news}" style="display:flex;align-items:center;gap:1rem;background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.07);border-radius:10px;padding:.9rem 1.1rem;margin-bottom:.6rem;text-decoration:none;" onmouseover="this.style.borderColor='rgba(114,215,240,.2)'" onmouseout="this.style.borderColor='rgba(114,215,240,.07)'">
        <span style="font-size:1.6rem;">📄</span>
        <div style="flex:1;">
          <p style="color:#e8f6fc;font-size:.88rem;font-weight:500;">${esc(n.titolo)}</p>
          <p style="color:#5d9ab8;font-size:.75rem;">${esc((n.nome_autore||'')+ ' '+(n.cognome_autore||''))}</p>
        </div>
        <p style="color:#5d9ab8;font-size:.74rem;flex-shrink:0;">${formatDate(n.data_pub)}</p>
      </a>`;
    });
    html += '<div style="height:1rem;"></div>';
  }

  // ── MEDIA ─────────────────────────────────────────────────────────────
  if (dati.media && dati.media.length) {
    html += `<p style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#5d9ab8;margin-bottom:.75rem;">🎬 Foto & Video</p>
    <div style="display:flex;flex-wrap:wrap;gap:.65rem;margin-bottom:1.5rem;">`;
    dati.media.forEach(m => {
      html += `
      <a href="${esc(m.url||'#')}" target="_blank" style="flex:0 0 calc(33.33% - .45rem);min-width:150px;background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.1);border-radius:10px;padding:.85rem;text-decoration:none;">
        <p style="font-size:.68rem;color:#1b9fd4;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.3rem;">${m.tipo==='video'?'📹 Video':'📸 Foto'}</p>
        <p style="color:#e8f6fc;font-size:.83rem;font-weight:500;">${esc(m.titolo)}</p>
      </a>`;
    });
    html += '</div>';
  }

  // ── DONAZIONI ─────────────────────────────────────────────────────────
  if (dati.donazioni && dati.donazioni.length) {
    html += `<p style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#5d9ab8;margin-bottom:.75rem;">💚 Progetti correlati</p>`;
    dati.donazioni.forEach(d => {
      html += `
      <a href="progetti.php?id=${d.id_pd}" style="display:flex;align-items:center;gap:1rem;background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.07);border-radius:10px;padding:.9rem 1.1rem;margin-bottom:.6rem;text-decoration:none;" onmouseover="this.style.borderColor='rgba(44,184,155,.25)'" onmouseout="this.style.borderColor='rgba(114,215,240,.07)'">
        <span style="font-size:1.6rem;">🌿</span>
        <div>
          <p style="color:#e8f6fc;font-size:.88rem;font-weight:500;">${esc(d.titolo)}</p>
          <p style="color:#5d9ab8;font-size:.75rem;">${esc((d.obiettivo||'').slice(0,80))}</p>
        </div>
      </a>`;
    });
  }

  // ── NESSUN RISULTATO ──────────────────────────────────────────────────
  if (!html) {
    html = `<div style="text-align:center;padding:3rem;color:#5d9ab8;">
      <div style="font-size:3rem;margin-bottom:1rem;">🌊</div>
      <p>Nessun risultato per "<strong style="color:#72d7f0;">${esc(query)}</strong>"</p>
    </div>`;
  }

  box.innerHTML = html;
}

function esc(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatDate(d) {
  if (!d) return '';
  try { return new Date(d).toLocaleDateString('it-IT',{day:'2-digit',month:'short',year:'numeric'}); } catch(e){return d;}
}
function sortResults() {}

// ── MODAL FEED IN INDEX ───────────────────────────────────────────────────
let indexModalPostId = null;

function apriModalIndex(card) {
  indexModalPostId = card.dataset.id;
  const url   = card.dataset.url || '';
  const isVid = /\.(mp4|webm|ogg)$/i.test(url);
  const isImg = /\.(jpg|jpeg|png|gif|webp)$/i.test(url);
  const likes = parseInt(card.dataset.likes) || 0;

  const mBox = document.getElementById('indexModalMedia');
  if (isVid) {
    mBox.innerHTML = `<video style="width:100%;max-height:340px;object-fit:cover;border-radius:16px 16px 0 0;display:block;" src="${escIdx(url)}" controls autoplay muted loop></video>`;
  } else if (isImg) {
    mBox.innerHTML = `<img style="width:100%;max-height:340px;object-fit:cover;border-radius:16px 16px 0 0;display:block;" src="${escIdx(url)}" alt="">`;
  } else {
    mBox.innerHTML = `<div style="height:180px;background:linear-gradient(135deg,#0b3d5e,#071e33);border-radius:16px 16px 0 0;display:flex;align-items:center;justify-content:center;font-size:5rem;">🌊</div>`;
  }

  document.getElementById('indexModalTipo').textContent   = isVid ? '📹 Video' : '📸 Foto';
  document.getElementById('indexModalTitolo').textContent = card.dataset.titolo;
  document.getElementById('indexModalAutore').textContent = 'Di ' + card.dataset.autore;
  document.getElementById('indexModalDesc').textContent   = card.dataset.desc;
  document.getElementById('indexModalLikeCount').textContent = likes;
  document.getElementById('indexModalLikeIcon').textContent  = card.dataset.liked === '1' ? '❤️' : '🤍';
  const likeBtn = document.getElementById('indexModalLikeBtn');
  likeBtn.style.background    = card.dataset.liked === '1' ? 'rgba(224,90,58,.2)' : 'rgba(11,61,94,.4)';
  likeBtn.style.borderColor   = card.dataset.liked === '1' ? 'rgba(224,90,58,.5)' : 'rgba(114,215,240,.2)';
  likeBtn.style.color         = card.dataset.liked === '1' ? '#e8836a' : '#c5e4f5';

  const overlay = document.getElementById('indexModalOverlay');
  overlay.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function chiudiIndexModal(e) {
  if (e.target === document.getElementById('indexModalOverlay')) chiudiIndexModalBtn();
}
function chiudiIndexModalBtn() {
  document.getElementById('indexModalOverlay').style.display = 'none';
  document.body.style.overflow = '';
  const v = document.querySelector('#indexModalMedia video');
  if (v) v.pause();
  indexModalPostId = null;
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') chiudiIndexModalBtn(); });

async function toggleLikeIndex() {
  <?php if (!isset($_SESSION['id'])): ?>
  window.location.href = 'Login.php?redirect=index.php'; return;
  <?php endif; ?>
  if (!indexModalPostId) return;
  const fd = new FormData();
  fd.append('like_post','1'); fd.append('id_post', indexModalPostId);
  try {
    const res = await fetch('feed.php', {method:'POST', body:fd});
    const d = await res.json();
    if (d.error === 'login') { window.location.href='Login.php'; return; }
    const btn = document.getElementById('indexModalLikeBtn');
    document.getElementById('indexModalLikeIcon').textContent  = d.liked ? '❤️' : '🤍';
    document.getElementById('indexModalLikeCount').textContent = d.count;
    btn.style.background  = d.liked ? 'rgba(224,90,58,.2)' : 'rgba(11,61,94,.4)';
    btn.style.borderColor = d.liked ? 'rgba(224,90,58,.5)' : 'rgba(114,215,240,.2)';
    btn.style.color       = d.liked ? '#e8836a' : '#c5e4f5';
    // Aggiorna card
    const card = document.querySelector(`.feed-card[data-id="${indexModalPostId}"]`);
    if (card) { card.dataset.liked = d.liked ? '1' : '0'; card.dataset.likes = d.count; }
  } catch(e) { console.error(e); }
}

function escIdx(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}





/* ══════════════════════════════════════════
   DONATION FILTERS
══════════════════════════════════════════ */
function filterDonations(filter, btn) {
  document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.donation-card').forEach(card => {
    const stato = card.dataset.stato;
    card.style.display = (filter === 'all' || stato === filter) ? 'block' : 'none';
  });
}

/* ══════════════════════════════════════════
   SCROLL ANIMATIONS
══════════════════════════════════════════ */
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: .15 });
document.querySelectorAll('.anim').forEach(el => observer.observe(el));

/* ══════════════════════════════════════════
   PROGRESS BARS ANIMATE ON VISIBLE
══════════════════════════════════════════ */
const progObserver = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.style.width = e.target.dataset.width || e.target.style.width;
    }
  });
}, { threshold: .2 });
document.querySelectorAll('.progress-fill').forEach(el => progObserver.observe(el));
</script>
</body>
</html>