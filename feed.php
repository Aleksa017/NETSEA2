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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;--text:#c5e4f5;--muted:#5d9ab8;--kelp:#2cb89b;--coral:#e05a3a;}
    html,body{height:100%;overflow:hidden;}
    body{font-family:'Outfit',sans-serif;background:#000;color:var(--text);cursor:none;}
    .cursor{width:10px;height:10px;background:var(--foam);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);mix-blend-mode:screen;}
    .cursor-ring{width:32px;height:32px;border:1.5px solid rgba(114,215,240,.4);border-radius:50%;position:fixed;top:0;left:0;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);}

    nav{position:fixed;top:0;left:0;right:0;z-index:200;height:56px;display:flex;align-items:center;padding:0 1.5rem;background:linear-gradient(180deg,rgba(4,17,30,.9),transparent);}
    .nav-logo{display:flex;align-items:center;gap:.5rem;text-decoration:none;font-family:'Cormorant Garamond',serif;font-size:1.4rem;font-weight:600;color:var(--pearl);}
    .nav-logo svg{width:28px;height:28px;}
    .nav-back{margin-left:auto;color:rgba(255,255,255,.6);text-decoration:none;font-size:.85rem;transition:color .2s;}
    .nav-back:hover{color:#fff;}

    /* FEED */
    .feed-container{height:100vh;overflow-y:scroll;scroll-snap-type:y mandatory;scrollbar-width:none;}
    .feed-container::-webkit-scrollbar{display:none;}

    .post-slide{height:100vh;scroll-snap-align:start;position:relative;display:flex;align-items:flex-end;overflow:hidden;cursor:pointer;}
    .post-bg{position:absolute;inset:0;background:linear-gradient(135deg,var(--deep),var(--ocean));z-index:0;}
    .post-bg img,.post-bg video{width:100%;height:100%;object-fit:cover;display:block;}
    .post-overlay{position:absolute;inset:0;background:linear-gradient(0deg,rgba(4,17,30,.85) 0%,rgba(4,17,30,.1) 50%,transparent 100%);z-index:1;}

    .post-content{position:relative;z-index:2;padding:1.5rem 5rem 2rem 1.5rem;width:100%;max-width:550px;}
    .post-autore{font-size:.82rem;color:rgba(255,255,255,.7);margin-bottom:.5rem;}
    .post-autore strong{color:#fff;}
    .post-titolo{font-family:'Cormorant Garamond',serif;font-size:1.6rem;color:#fff;font-weight:400;line-height:1.2;margin-bottom:.5rem;}
    .post-desc{font-size:.85rem;color:rgba(255,255,255,.75);line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}

    /* AZIONI LATO */
    .post-actions{position:absolute;right:1rem;bottom:2rem;z-index:2;display:flex;flex-direction:column;align-items:center;gap:1.25rem;}
    .action-btn{display:flex;flex-direction:column;align-items:center;gap:.3rem;cursor:pointer;color:rgba(255,255,255,.85);background:none;border:none;font-family:'Outfit',sans-serif;}
    .action-btn .icon{width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;font-size:1.3rem;transition:all .2s;border:1px solid rgba(255,255,255,.15);}
    .action-btn:hover .icon,.action-btn:focus .icon{background:rgba(255,255,255,.22);transform:scale(1.1);}
    .action-btn.liked .icon{background:rgba(224,90,58,.35);border-color:rgba(224,90,58,.6);}
    .action-btn .lbl{font-size:.72rem;color:rgba(255,255,255,.7);}

    .tipo-badge{position:absolute;top:4.5rem;left:1rem;z-index:2;padding:.2rem .7rem;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;background:rgba(27,159,212,.25);border:1px solid rgba(27,159,212,.4);color:var(--foam);backdrop-filter:blur(4px);}

    .loading{text-align:center;padding:2rem;color:var(--muted);font-size:.875rem;height:100vh;display:flex;align-items:center;justify-content:center;scroll-snap-align:start;}
    .empty-slide{height:100vh;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:1rem;color:var(--muted);background:var(--ink);scroll-snap-align:start;}

    /* ── MODAL DETTAGLIO POST ── */
    .modal-overlay{position:fixed;inset:0;z-index:500;background:rgba(4,17,30,.92);backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s;}
    .modal-overlay.open{opacity:1;pointer-events:all;}
    .modal{background:rgba(11,61,94,.35);border:1px solid rgba(114,215,240,.2);border-radius:20px;max-width:680px;width:92%;max-height:90vh;overflow-y:auto;position:relative;scrollbar-width:thin;}
    .modal-media{width:100%;max-height:380px;object-fit:cover;border-radius:16px 16px 0 0;display:block;}
    .modal-media-placeholder{height:200px;background:linear-gradient(135deg,var(--ocean),var(--deep));display:flex;align-items:center;justify-content:center;font-size:6rem;border-radius:16px 16px 0 0;}
    .modal-body{padding:1.5rem;}
    .modal-tipo{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--foam);margin-bottom:.6rem;}
    .modal-titolo{font-family:'Cormorant Garamond',serif;font-size:1.7rem;color:var(--pearl);font-weight:400;line-height:1.2;margin-bottom:.6rem;}
    .modal-autore{color:var(--muted);font-size:.82rem;margin-bottom:1rem;}
    .modal-autore strong{color:var(--wave);}
    .modal-desc{color:var(--text);font-size:.9rem;line-height:1.75;margin-bottom:1.5rem;}
    .modal-actions{display:flex;align-items:center;gap:1rem;}
    .modal-like-btn{display:flex;align-items:center;gap:.5rem;padding:.65rem 1.4rem;border-radius:50px;border:1px solid rgba(114,215,240,.2);background:rgba(11,61,94,.4);color:var(--text);font-family:'Outfit',sans-serif;font-size:.9rem;cursor:pointer;transition:all .2s;}
    .modal-like-btn:hover{border-color:rgba(224,90,58,.4);background:rgba(224,90,58,.1);}
    .modal-like-btn.liked{background:rgba(224,90,58,.2);border-color:rgba(224,90,58,.5);color:#e8836a;}
    .modal-close{position:absolute;top:1rem;right:1rem;width:36px;height:36px;border-radius:50%;background:rgba(4,17,30,.7);border:1px solid rgba(114,215,240,.2);color:#fff;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;z-index:10;}
    .modal-close:hover{background:rgba(224,90,58,.3);}

    /* FAB pubblica */
    .fab{position:fixed;bottom:2rem;right:2rem;z-index:300;background:var(--wave);color:#04111e;border-radius:50px;padding:.85rem 1.5rem;font-family:'Outfit',sans-serif;font-weight:700;font-size:.9rem;text-decoration:none;display:flex;align-items:center;gap:.5rem;box-shadow:0 8px 30px rgba(27,159,212,.4);transition:all .2s;}
    .fab:hover{background:var(--foam);transform:translateY(-2px);}
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
  <a href="javascript:history.back()" class="nav-back">✕ Chiudi</a>
</nav>

<!-- MODAL DETTAGLIO -->
<div class="modal-overlay" id="modalOverlay" onclick="chiudiModal(event)">
  <div class="modal" id="modal">
    <button class="modal-close" onclick="chiudiModalBtn()">✕</button>
    <div id="modalMedia"></div>
    <div class="modal-body">
      <p class="modal-tipo" id="modalTipo"></p>
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
  <span class="tipo-badge">🎬 Contenuto</span>

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
  const isVid  = /\.(mp4|webm|ogg)$/i.test(url);
  const isImg  = /\.(jpg|jpeg|png|gif|webp)$/i.test(url);
  const liked  = slide.dataset.liked === '1';
  const likes  = parseInt(slide.dataset.likes) || 0;

  // Media
  const mBox = document.getElementById('modalMedia');
  if (isVid) {
    mBox.innerHTML = `<video class="modal-media" src="${esc(url)}" controls autoplay muted loop></video>`;
  } else if (isImg) {
    mBox.innerHTML = `<img class="modal-media" src="${esc(url)}" alt="">`;
  } else {
    mBox.innerHTML = `<div class="modal-media-placeholder">🌊</div>`;
  }

  document.getElementById('modalTipo').textContent    = '🎬 Contenuto';
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
document.querySelectorAll('.post-slide').forEach(s=>observer.observe(s));

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
    <span class="tipo-badge">🎬 Contenuto</span>
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
  return div;
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
</script>
</body>
</html>