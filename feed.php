<?php
require 'config.php';

// ── AZIONE LIKE (AJAX) ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['id'])) { echo json_encode(['error'=>'login']); exit(); }
    $id_post   = (int)$_POST['id_post'];
    $id_utente = (int)$_SESSION['id'];
    $check = $connessione->prepare("SELECT 1 FROM like_media WHERE id_post=? AND id_utente=?");
    $check->execute([$id_post, $id_utente]);
    if ($check->fetch()) {
        $connessione->prepare("DELETE FROM like_media WHERE id_post=? AND id_utente=?")->execute([$id_post,$id_utente]);
        $liked = false;
    } else {
        $connessione->prepare("INSERT INTO like_media (id_post,id_utente) VALUES (?,?)")->execute([$id_post,$id_utente]);
        $liked = true;
    }
    $cnt = $connessione->prepare("SELECT COUNT(*) FROM like_media WHERE id_post=?");
    $cnt->execute([$id_post]);
    echo json_encode(['liked'=>$liked,'count'=>(int)$cnt->fetchColumn()]);
    exit();
}

// ── CARICA POST (scroll infinito JSON) ──────────────────────────────────
$offset = (int)($_GET['offset'] ?? 0);
$limit  = 10;

// LIMIT/OFFSET come interi diretti nella query per compatibilità MariaDB
$stmt = $connessione->query("
    SELECT m.*, u.nome, u.cognome, u.username,
           (SELECT COUNT(*) FROM like_media l WHERE l.id_post = m.id_post) AS like_count
    FROM media m
    LEFT JOIN utente u ON m.id_utente = u.id_utente
    ORDER BY m.data_pub DESC
    LIMIT $limit OFFSET $offset
");
$posts = $stmt->fetchAll();

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($posts); exit();
}

// Like dell'utente corrente
$miei_like = [];
if (isset($_SESSION['id']) && !empty($posts)) {
    $ids = implode(',', array_map('intval', array_column($posts, 'id_post')));
    if ($ids) {
        $lk = $connessione->query("SELECT id_post FROM like_media WHERE id_utente={$_SESSION['id']} AND id_post IN ($ids)");
        foreach ($lk->fetchAll() as $r) $miei_like[$r['id_post']] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feed — NetSea</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<nav class="nav-feed">
  <a href="index.php" class="nav-logo">
    <svg viewBox="0 0 40 40" fill="none">
      <circle cx="20" cy="20" r="18" fill="rgba(27,159,212,.15)" stroke="rgba(114,215,240,.3)" stroke-width="1"/>
      <path d="M8 22 Q12 16 16 22 Q20 28 24 22 Q28 16 32 22" stroke="#72d7f0" stroke-width="2" fill="none" stroke-linecap="round"/>
    </svg>
    NetSea
  </a>
  <a href="javascript:history.back()" class="nav-back">✕ Chiudi</a>
</nav>

<!-- MODAL DETTAGLIO -->
<div class="modal-overlay" id="modalOverlay" onclick="chiudiModal(event)">
  <div class="modal" id="modal">
    <button class="modal-close" onclick="chiudiModalBtn()">✕</button>
    <div id="modalMedia"></div>
    <div class="modal-body">
      <p class="modal-tipo" id="modalTipo" style="font-size:.75rem;color:var(--wave);letter-spacing:.1em;text-transform:uppercase;margin-bottom:.5rem;"></p>
      <h2 class="modal-titolo" id="modalTitolo"></h2>
      <p class="modal-autore" id="modalAutore"></p>
      <p class="modal-desc" id="modalDesc"></p>
      <div class="modal-actions">
        <button class="modal-like-btn" id="modalLikeBtn" onclick="toggleLikeModal()">
          <span id="modalLikeIcon">🤍</span>
          <span id="modalLikeCount">0</span> Mi piace
        </button>
      </div>
    </div>
  </div>
</div>

<div class="feed-container" id="feedContainer">
<?php if (empty($posts)): ?>
  <div class="empty-slide">
    <div style="font-size:4rem;">🌊</div>
    <p>Nessun contenuto ancora.</p>
    <?php if (in_array($_SESSION['ruolo'] ?? '', ['ricercatore','admin'])): ?>
      <a href="crea_contenuto.php" style="color:var(--wave);margin-top:.5rem;">+ Pubblica il primo contenuto</a>
    <?php endif; ?>
  </div>
<?php else: ?>

<?php foreach ($posts as $p):
  $liked   = isset($miei_like[$p['id_post']]);
  $isVideo = !empty($p['url']) && preg_match('/\.(mp4|webm|ogg)$/i', $p['url']);
  $isImg   = !empty($p['url']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $p['url']);
  $autore  = trim(($p['nome'] ?? '') . ' ' . ($p['cognome'] ?? '')) ?: 'NetSea';
  $data    = $p['data_pub'] ? date('d M Y', strtotime($p['data_pub'])) : '';
  $desc_short = mb_substr($p['descrizione'] ?? '', 0, 120);
?>
<div class="post-slide"
     data-id="<?= $p['id_post'] ?>"
     data-titolo="<?= htmlspecialchars($p['titolo'], ENT_QUOTES) ?>"
     data-desc="<?= htmlspecialchars($p['descrizione'] ?? '', ENT_QUOTES) ?>"
     data-autore="<?= htmlspecialchars($autore, ENT_QUOTES) ?>"
     data-data="<?= $data ?>"
     data-url="<?= htmlspecialchars($p['url'] ?? '', ENT_QUOTES) ?>"
     data-likes="<?= (int)$p['like_count'] ?>"
     data-liked="<?= $liked ? '1' : '0' ?>"
     onclick="apriModal(this)">

  <div class="post-bg">
    <?php if ($isVideo): ?>
      <video src="<?= htmlspecialchars($p['url']) ?>" loop muted playsinline preload="none"></video>
    <?php elseif ($isImg): ?>
      <img src="<?= htmlspecialchars($p['url']) ?>" alt="" loading="lazy">
    <?php else: ?>
      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:8rem;opacity:.15;">🌊</div>
    <?php endif; ?>
  </div>
  <div class="post-overlay"></div>
  <span class="tipo-badge"><?= $isVideo ? '🎬 VIDEO' : '📷 FOTO' ?></span>

  <div class="post-content">
    <p class="post-autore">📍 <strong><?= htmlspecialchars($autore) ?></strong> · <?= $data ?></p>
    <h2 class="post-titolo"><?= htmlspecialchars($p['titolo']) ?></h2>
    <p class="post-desc"><?= htmlspecialchars($desc_short) ?><?= mb_strlen($p['descrizione'] ?? '') > 120 ? '…' : '' ?></p>
  </div>

  <div class="post-actions" onclick="event.stopPropagation()">
    <button class="action-btn like-btn <?= $liked ? 'liked' : '' ?>"
            data-id="<?= $p['id_post'] ?>" onclick="toggleLike(this)">
      <div class="icon"><?= $liked ? '❤️' : '🤍' ?></div>
      <span class="lbl like-count"><?= (int)$p['like_count'] ?></span>
    </button>
    <div class="action-btn">
      <div class="icon">👁</div>
      <span class="lbl"><?= (int)($p['visualizzazioni'] ?? 0) ?></span>
    </div>
    <button class="action-btn" onclick="condividi(<?= $p['id_post'] ?>)">
      <div class="icon">🔗</div>
      <span class="lbl">Share</span>
    </button>
  </div>
</div>
<?php endforeach; ?>

<div class="loading" id="loadMore">
  <?= count($posts) < $limit ? '— Fine del feed —' : 'Caricamento…' ?>
</div>

<?php endif; ?>
</div>

<?php if (isset($_SESSION['id']) && in_array($_SESSION['ruolo'] ?? '', ['ricercatore','admin'])): ?>
<a href="crea_contenuto.php" class="fab">✨ Pubblica</a>
<?php endif; ?>

<script>
const loggedIn = <?= isset($_SESSION['id']) ? 'true' : 'false' ?>;
const cur=document.getElementById('cursor'),ring=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.cssText=`left:${mx}px;top:${my}px`;});
(function loop(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.cssText=`left:${rx}px;top:${ry}px`;requestAnimationFrame(loop);})();

// ── MODAL ─────────────────────────────────────────────────────────────────
let modalPostId = null;

function apriModal(slide) {
  modalPostId = slide.dataset.id;
  const url    = slide.dataset.url;
  const isVidModal = /\.(mp4|webm|ogg)$/i.test(url||'');
  const tipoEl = document.getElementById('modalTipo');
  if(tipoEl) tipoEl.textContent = isVidModal ? '🎬 VIDEO' : '📷 FOTO';
  const isVid  = /\.(mp4|webm|ogg)$/i.test(url);
  const isImg  = /\.(jpg|jpeg|png|gif|webp)$/i.test(url);
  const liked  = slide.dataset.liked === '1'; // sempre aggiornato
  const likes  = parseInt(slide.dataset.likes) || 0;

  // Media
  const mBox = document.getElementById('modalMedia');
  if (isVid) {
    mBox.innerHTML = `<video class="modal-media" src="${esc(url)}" controls autoplay loop></video>`;
  } else if (isImg) {
    mBox.innerHTML = `<img class="modal-media" src="${esc(url)}" alt="">`;
  } else {
    mBox.innerHTML = `<div class="modal-media-placeholder">🌊</div>`;
  }

  // tipo già impostato sopra
  document.getElementById('modalTitolo').textContent  = slide.dataset.titolo;
  document.getElementById('modalAutore').innerHTML    = `Di <strong>${esc(slide.dataset.autore)}</strong> · ${esc(slide.dataset.data)}`;
  document.getElementById('modalDesc').textContent    = slide.dataset.desc;
  document.getElementById('modalLikeCount').textContent = likes;

  const likeBtn = document.getElementById('modalLikeBtn');
  likeBtn.classList.toggle('liked', liked);
  document.getElementById('modalLikeIcon').textContent = liked ? '❤️' : '🤍';

  document.getElementById('modalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function chiudiModal(e) {
  if (e.target === document.getElementById('modalOverlay')) chiudiModalBtn();
}
function chiudiModalBtn() {
  document.getElementById('modalOverlay').classList.remove('open');
  document.body.style.overflow = '';
  // Ferma video nel modal
  const v = document.querySelector('#modalMedia video');
  if (v) v.pause();
  modalPostId = null;
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') chiudiModalBtn(); });

// Like dal modal
async function toggleLikeModal() {
  if (!loggedIn) { window.location.href='Login.php?redirect=feed.php'; return; }
  if (!modalPostId) return;
  // Trova il bottone sul feed corrispondente e "cliccalo"
  const slideBtn = document.querySelector(`.like-btn[data-id="${modalPostId}"]`);
  if (slideBtn) { await toggleLike(slideBtn); }
  // Aggiorna anche il modal
  const liked = slideBtn ? slideBtn.classList.contains('liked') : false;
  const count = slideBtn ? slideBtn.querySelector('.like-count').textContent : '0';
  document.getElementById('modalLikeBtn').classList.toggle('liked', liked);
  document.getElementById('modalLikeIcon').textContent = liked ? '❤️' : '🤍';
  document.getElementById('modalLikeCount').textContent = count;
  // Aggiorna data-liked sullo slide
  const slide = document.querySelector(`.post-slide[data-id="${modalPostId}"]`);
  if (slide) { slide.dataset.liked = liked ? '1' : '0'; slide.dataset.likes = count; }
}

// ── LIKE SUL FEED ─────────────────────────────────────────────────────────
async function toggleLike(btn) {
  if (!loggedIn) { window.location.href='Login.php?redirect=feed.php'; return; }
  const id = btn.dataset.id;
  const fd = new FormData();
  fd.append('like_post','1'); fd.append('id_post', id);
  try {
    const res = await fetch('feed.php',{method:'POST',body:fd});
    const d = await res.json();
    if (d.error==='login') { window.location.href='Login.php?redirect=feed.php'; return; }
    btn.classList.toggle('liked', d.liked);
    btn.querySelector('.icon').textContent = d.liked ? '❤️' : '🤍';
    btn.querySelector('.like-count').textContent = d.count;
  } catch(e){ console.error(e); }
}

// ── CONDIVIDI ─────────────────────────────────────────────────────────────
function condividi(id) {
  const url = location.origin + location.pathname + '?post=' + id;
  if (navigator.share) navigator.share({title:'NetSea',url});
  else navigator.clipboard.writeText(url).then(()=>alert('Link copiato!'));
}

// ── AUTOPLAY VIDEO ────────────────────────────────────────────────────────
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => {
    const vid = e.target.querySelector('video');
    if (!vid) return;
    if (e.isIntersecting) vid.play().catch(()=>{});
    else { vid.pause(); vid.currentTime=0; }
  });
},{threshold:0.7});
document.querySelectorAll('.post-slide').forEach(s=>{
  observer.observe(s);
  // Detect orientamento video per class .vertical
  const vid = s.querySelector('video');
  if (vid) {
    vid.addEventListener('loadedmetadata', () => {
      if (vid.videoHeight > vid.videoWidth) s.classList.add('vertical');
      else s.classList.remove('vertical');
    }, {once:true});
  }
});

// ── SCROLL INFINITO ───────────────────────────────────────────────────────
let offset = <?= count($posts) ?>;
let loading = false;
let exhausted = <?= count($posts) < $limit ? 'true' : 'false' ?>;
const fc = document.getElementById('feedContainer');
const loadEl = document.getElementById('loadMore');

fc && fc.addEventListener('scroll', async () => {
  if (loading||exhausted) return;
  if (fc.scrollTop+fc.clientHeight < fc.scrollHeight-fc.clientHeight*1.5) return;
  loading=true;
  try {
    const res = await fetch(`feed.php?json=1&offset=${offset}`);
    const nuovi = await res.json();
    if (!nuovi.length){exhausted=true;if(loadEl)loadEl.textContent='— Fine del feed —';return;}
    nuovi.forEach(p=>{
      const slide=creaSlide(p);
      loadEl?fc.insertBefore(slide,loadEl):fc.appendChild(slide);
      observer.observe(slide);
    });
    offset+=nuovi.length;
    if(nuovi.length<10){exhausted=true;if(loadEl)loadEl.textContent='— Fine del feed —';}
  } catch(e){console.error(e);}
  finally{loading=false;}
});

function creaSlide(p) {
  const div=document.createElement('div');
  div.className='post-slide';
  div.dataset.id=p.id_post;
  div.dataset.titolo=p.titolo||'';
  div.dataset.desc=p.descrizione||'';
  div.dataset.autore=((p.nome||'')+' '+(p.cognome||'')).trim()||'NetSea';
  div.dataset.data=p.data_pub?new Date(p.data_pub).toLocaleDateString('it-IT',{day:'2-digit',month:'short',year:'numeric'}):'';
  div.dataset.url=p.url||'';
  div.dataset.likes=p.like_count||0;
  div.dataset.liked='0';
  div.setAttribute('onclick','apriModal(this)');
  const isVid=/\.(mp4|webm|ogg)$/i.test(p.url||'');
  const isImg=/\.(jpg|jpeg|png|gif|webp)$/i.test(p.url||'');
  const autore=div.dataset.autore;
  div.innerHTML=`
    <div class="post-bg">
      ${isVid?`<video src="${esc(p.url)}" loop muted playsinline preload="none"></video>`
              :isImg?`<img src="${esc(p.url)}" alt="" loading="lazy">`
              :'<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:8rem;opacity:.15;">🌊</div>'}
    </div>
    <div class="post-overlay"></div>
    <span class="tipo-badge">${isVid?'🎬 VIDEO':'📷 FOTO'}</span>
    <div class="post-content">
      <p class="post-autore">📍 <strong>${esc(autore)}</strong> · ${esc(div.dataset.data)}</p>
      <h2 class="post-titolo">${esc(p.titolo||'')}</h2>
      <p class="post-desc">${esc((p.descrizione||'').slice(0,120))}</p>
    </div>
    <div class="post-actions" onclick="event.stopPropagation()">
      <button class="action-btn like-btn" data-id="${p.id_post}" onclick="toggleLike(this)">
        <div class="icon">🤍</div><span class="lbl like-count">${p.like_count||0}</span>
      </button>
      <div class="action-btn"><div class="icon">👁</div><span class="lbl">${p.visualizzazioni||0}</span></div>
      <button class="action-btn" onclick="condividi(${p.id_post})"><div class="icon">🔗</div><span class="lbl">Share</span></button>
    </div>`;
  // Detect orientamento video
  const vidEl = div.querySelector('video');
  if (vidEl) {
    vidEl.addEventListener('loadedmetadata', () => {
      if (vidEl.videoHeight > vidEl.videoWidth) div.classList.add('vertical');
      else div.classList.remove('vertical');
    }, {once:true});
  }
  return div;
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
</script>
</body>
</html>