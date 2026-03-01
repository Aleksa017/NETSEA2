<?php
require 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header("Location: progetti.php"); exit(); }

$stmt = $connessione->prepare("SELECT * FROM progetto WHERE id_pd = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { header("Location: progetti.php"); exit(); }

// ── AZIONE DONAZIONE ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dona'])) {
    if (!isset($_SESSION['id'])) {
        header("Location: Login.php?redirect=progetto_detail.php?id=$id"); exit();
    }
    $importo = (float)str_replace(',', '.', $_POST['importo'] ?? 0);
    if ($importo >= 1 && $importo <= 10000) {
        $connessione->prepare("UPDATE progetto SET raccolto = raccolto + ? WHERE id_pd = ?")
                    ->execute([$importo, $id]);
    }
    header("Location: progetto_detail.php?id=$id&ok=1"); exit();
}

$raccolto = (float)($p['raccolto'] ?? 0);
$budget   = (float)($p['budget']   ?? 0);
$perc     = $budget > 0 ? min(100, round($raccolto / $budget * 100)) : 0;
$is_attivo = strtolower($p['stato'] ?? '') === 'attivo';
$data_inizio = $p['data_i'] ? date('d M Y', strtotime($p['data_i'])) : '—';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($p['titolo']) ?> — NetSea</title>
  <link rel="stylesheet" href="style.css">
  <style>
  /* Minimal styles per modal donazione */
  .donation-overlay{position:fixed;inset:0;background:rgba(4,17,30,.6);display:flex;align-items:center;justify-content:center;z-index:10000}
  .donation-modal{background:#071e33;color:#c5e4f5;border-radius:10px;padding:1.25rem;max-width:520px;width:90%;box-shadow:0 8px 30px rgba(0,0,0,.6);position:relative}
  .donation-close{position:absolute;right:.5rem;top:.5rem;background:none;border:none;color:#c5e4f5;font-size:1.1rem;cursor:pointer}
  .pm-list{display:flex;gap:.5rem;margin-top:.5rem}
  .pm-btn{background:rgba(27,159,212,.15);border:1px solid rgba(114,215,240,.12);color:var(--text);padding:.5rem .75rem;border-radius:8px;cursor:pointer}
  </style>
</head>
<body>
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>
<nav>
  <a href="index.php" class="nav-logo"><img src="logo.svg" alt="NetSea" style="height:56px;width:auto;object-fit:contain;display:block;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));"></a>
  <a href="progetti.php" class="nav-back">← Tutti i progetti</a>
</nav>

<?php if (isset($_GET['ok'])): ?>
  <div class="flash" style="margin-top:1rem;">✅ Grazie per la tua donazione! Il contributo è stato registrato.</div>
<?php endif; ?>

<div class="hero">
  <div class="hero-inner">
    <span class="stato-badge <?= $is_attivo ? 'stato-attivo' : 'stato-concluso' ?>">
      <?= $is_attivo ? '🟢 Progetto attivo' : '⚫ Progetto concluso' ?>
    </span>
    <h1><?= htmlspecialchars($p['titolo']) ?></h1>
    <p class="data-inizio">📅 Avviato il <?= $data_inizio ?></p>

    <!-- PROGRESS -->
    <div class="progress-box">
      <div class="prog-nums">
        <div class="prog-raccolto">
          € <?= number_format($raccolto, 2, ',', '.') ?>
          <span>raccolti</span>
        </div>
        <div class="prog-budget">
          obiettivo: <strong>€ <?= number_format($budget, 2, ',', '.') ?></strong>
        </div>
      </div>
      <div class="bar-track">
        <div class="bar-fill" style="width:<?= $perc ?>%"></div>
      </div>
      <p class="prog-perc"><strong><?= $perc ?>%</strong> dell'obiettivo raggiunto</p>
    </div>

    <!-- DONAZIONE -->
    <?php if ($is_attivo): ?>
      <div class="dona-section">
        <h3>💚 Sostieni questo progetto</h3>
        <p>Il tuo contributo va direttamente alla ricerca e alla protezione degli ecosistemi marini.</p>
        <?php if (isset($_SESSION['id'])): ?>
          <form method="POST" class="dona-form" id="donaForm">
            <div class="dona-input-wrap">
              <span>€</span>
              <input type="number" name="importo" class="dona-input"
                     placeholder="10.00" min="1" max="10000" step="0.01" required>
            </div>
            <button type="button" id="donaBtn" class="btn-dona">💚 Dona ora</button>
            <p class="hint">Min. €1 — Max. €10.000</p>
          </form>
        <?php else: ?>
          <p class="login-hint">
            <a href="Login.php?redirect=progetto_detail.php?id=<?= $id ?>">Accedi</a> per donare a questo progetto.
          </p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <p style="color:var(--muted);font-size:.875rem;margin-top:1rem;">Questo progetto è concluso. Grazie a tutti i donatori! 🙏</p>
    <?php endif; ?>
  </div>
</div>

<div class="main">
  <!-- DATI RAPIDI -->
  <div class="dati">
    <div class="dato">
      <p class="dato-lbl">Stato</p>
      <p class="dato-val"><?= htmlspecialchars(ucfirst($p['stato'] ?? '—')) ?></p>
    </div>
    <div class="dato">
      <p class="dato-lbl">Avvio</p>
      <p class="dato-val"><?= $data_inizio ?></p>
    </div>
    <div class="dato">
      <p class="dato-lbl">Raccolto</p>
      <p class="dato-val" style="color:var(--kelp);">€ <?= number_format($raccolto,2,',','.') ?></p>
    </div>
    <div class="dato">
      <p class="dato-lbl">Obiettivo</p>
      <p class="dato-val">€ <?= number_format($budget,2,',','.') ?></p>
    </div>
    <div class="dato">
      <p class="dato-lbl">Completamento</p>
      <p class="dato-val"><?= $perc ?>%</p>
    </div>
  </div>

  <!-- DESCRIZIONE OBIETTIVO -->
  <p class="sez-title">🎯 Obiettivo del progetto</p>
  <p class="obiettivo-text"><?= htmlspecialchars($p['obiettivo'] ?? 'Nessuna descrizione disponibile.') ?></p>
</div>

<script>
// AJAX per mostrare dettagli metodo di pagamento in overlay
// Flusso:
// 1) intercettiamo il click su un bottone `.pm-btn` (delegation sull'intero documento)
// 2) invochiamo `fetch()` verso `api/donation_method.php` con `method` e `id` del progetto
// 3) riceviamo JSON { html: '...' } e inseriamo l'HTML nell'overlay creato dinamicamente
// 4) gestiamo errori di rete e risposta non valida
(function(){
  // helper per query selector
  function qs(sel){ return document.querySelector(sel); }
  const overlayId = 'donationOverlay';
  const form = qs('#donaForm');
  const donaBtn = qs('#donaBtn');
  const pid = <?= (int)$id ?>;

  // Crea overlay (ma non lo mostra) — lo riusiamo
  function createOverlay(){
    let ov = qs('#' + overlayId);
    if (ov) return ov;
    ov = document.createElement('div');
    ov.id = overlayId;
    ov.className = 'donation-overlay';
    ov.innerHTML = '<div class="donation-modal"><button class="donation-close">✕</button><div class="donation-content"></div></div>';
    document.body.appendChild(ov);
    ov.addEventListener('click', function(ev){ if (ev.target === ov) ov.style.display = 'none'; });
    ov.querySelector('.donation-close').addEventListener('click', function(){ ov.style.display = 'none'; });
    return ov;
  }

  // Mostra il modal con i pulsanti di metodo (richiesto solo dopo click su Dona ora)
  function showMethodSelection(amount){
    const ov = createOverlay();
    const html = `
      <h3>Seleziona metodo di pagamento</h3>
      <p>Importo: <strong>€ ${Number(amount).toFixed(2)}</strong></p>
      <div class="pm-list">
        <button class="pm-btn" data-method="paypal">PayPal</button>
        <button class="pm-btn" data-method="card">Carta di credito</button>
        <button class="pm-btn" data-method="bonifico">Bonifico</button>
      </div>
    `;
    ov.querySelector('.donation-content').innerHTML = html;
    ov.style.display = 'flex';

    // attach listeners to the buttons inside the modal
    ov.querySelectorAll('.pm-btn').forEach(btn => {
      btn.addEventListener('click', function(){
        const method = this.getAttribute('data-method');

        // Form HTML generato direttamente in JS — nessuna fetch necessaria
        const inp = 'width:100%;padding:.6rem .9rem;background:rgba(4,17,30,.6);border:1px solid rgba(114,215,240,.2);border-radius:8px;color:#e8f6fc;font-size:.95rem;box-sizing:border-box;';
        const lbl = 'font-size:.78rem;color:#72d7f0;display:block;margin-bottom:.3rem;';
        let methodHtml = '';
        if (method === 'card') {
          methodHtml = `
            <h3 style="margin-bottom:1rem;">💳 Carta di credito</h3>
            <p style="font-size:.8rem;color:#5d9ab8;margin-bottom:1.25rem;">Simulazione — nessun addebito reale</p>
            <div style="display:flex;flex-direction:column;gap:.85rem;">
              <div><label style="${lbl}">Numero carta</label>
                <input id="card_number" type="text" placeholder="4242 4242 4242 4242" maxlength="19" style="${inp}"></div>
              <div style="display:flex;gap:.75rem;">
                <div style="flex:1"><label style="${lbl}">Scadenza (MM/AA)</label>
                  <input id="card_exp" type="text" placeholder="12/27" maxlength="5" style="${inp}"></div>
                <div style="flex:1"><label style="${lbl}">CVC</label>
                  <input id="card_cvc" type="text" placeholder="123" maxlength="4" style="${inp}"></div>
              </div>
              <div><label style="${lbl}">Titolare</label>
                <input id="card_name" type="text" placeholder="Mario Rossi" style="${inp}"></div>
            </div>`;
        } else if (method === 'paypal') {
          methodHtml = `
            <h3 style="margin-bottom:1rem;">🅿️ PayPal</h3>
            <p style="font-size:.8rem;color:#5d9ab8;margin-bottom:1.25rem;">Simulazione — nessun addebito reale</p>
            <div style="display:flex;flex-direction:column;gap:.85rem;">
              <div><label style="${lbl}">Email PayPal</label>
                <input id="pp_email" type="email" placeholder="nome@esempio.it" style="${inp}"></div>
              <div><label style="${lbl}">Password</label>
                <input id="pp_pass" type="password" placeholder="••••••••" style="${inp}"></div>
            </div>`;
        } else if (method === 'bonifico') {
          methodHtml = `
            <h3 style="margin-bottom:1rem;">🏦 Bonifico Bancario</h3>
            <div style="background:rgba(27,159,212,.07);border:1px solid rgba(27,159,212,.2);border-radius:10px;padding:1.1rem 1.25rem;display:flex;flex-direction:column;gap:.6rem;">
              <div style="display:flex;justify-content:space-between;"><span style="font-size:.78rem;color:#5d9ab8;">Beneficiario</span><span style="color:#e8f6fc;font-weight:500;">NetSea ETS</span></div>
              <div style="display:flex;justify-content:space-between;"><span style="font-size:.78rem;color:#5d9ab8;">IBAN</span><span style="color:#72d7f0;font-family:monospace;">IT60 X054 2811 1010 0000 0123 456</span></div>
              <div style="display:flex;justify-content:space-between;"><span style="font-size:.78rem;color:#5d9ab8;">Causale</span><span style="color:#e8f6fc;">Donazione Progetto #${pid}</span></div>
            </div>
            <p style="font-size:.74rem;color:rgba(114,215,240,.4);margin-top:.75rem;">⏱ Il bonifico viene registrato entro 2-3 giorni lavorativi.</p>`;
        }

        function attachFormatting(content){
          const cardNumEl = content.querySelector('#card_number');
          const expEl = content.querySelector('#card_exp');
          if (cardNumEl){
            cardNumEl.addEventListener('input', function(){
              const digits = this.value.replace(/\D/g,'').slice(0,16);
              const parts = digits.match(/.{1,4}/g);
              this.value = parts ? parts.join(' ') : digits;
            });
          }
          if (expEl){
            expEl.addEventListener('input', function(){
              const digits = this.value.replace(/\D/g,'').slice(0,4);
              if (digits.length <= 2) this.value = digits;
              else this.value = digits.slice(0,2) + '/' + digits.slice(2);
            });
          }
        }

        const content = ov.querySelector('.donation-content');
        content.innerHTML = methodHtml + `<p style="margin-top:1.25rem;display:flex;gap:.75rem;"><button class="confirm-pay btn-dona">Conferma e Paga</button><button class="cancel-pay" style="background:transparent;border:1px solid rgba(114,215,240,.2);color:#5d9ab8;padding:.6rem 1.2rem;border-radius:8px;cursor:pointer;">Annulla</button></p>`;
        attachFormatting(content);

        // Conferma: submit del form
        content.querySelector('.confirm-pay').addEventListener('click', function(){
              // assicuriamoci che l'importo sia presente
              const impEl = form.querySelector('input[name="importo"]');
              if (!impEl || Number(impEl.value) < 1) { alert('Importo non valido'); return; }

              // controlli specifici per il metodo 'card'
              if (method === 'card'){
                const cardNumEl = content.querySelector('#card_number');
                const expEl = content.querySelector('#card_exp');
                const cvcEl = content.querySelector('#card_cvc');
                const cardNum = cardNumEl ? cardNumEl.value.trim() : '';
                const exp = expEl ? expEl.value.trim() : '';
                const cvc = cvcEl ? cvcEl.value.trim() : '';

                // Validazione numero: solo verifica che contenga tra 13 e 19 cifre
                const digitsOnly = cardNum.replace(/\D/g, '');
                if (!/^[0-9]{13,19}$/.test(digitsOnly)) { alert('Numero carta non valido (deve contenere 13–19 cifre)'); return; }

                // Validazione scadenza MM/AA e non scaduta
                if (!/^\s*\d{2}\/\d{2}\s*$/.test(exp)) { alert('Formato data scadenza non valido (MM/AA)'); return; }
                const [mm, yy] = exp.split('/').map(s=>parseInt(s,10));
                if (!(mm >=1 && mm <=12)) { alert('Mese di scadenza non valido'); return; }
                const now = new Date();
                const fullYear = 2000 + yy;
                const expDate = new Date(fullYear, mm, 0, 23,59,59);
                if (expDate < now) { alert('Carta scaduta'); return; }

                // CVC: 3 o 4 cifre
                if (!/^[0-9]{3,4}$/.test(cvc)) { alert('CVC non valido'); return; }
              }

              // impostiamo campi nascosti nel form e submit
              let methodInput = form.querySelector('input[name="method"]');
              if (!methodInput) { methodInput = document.createElement('input'); methodInput.type='hidden'; methodInput.name='method'; form.appendChild(methodInput); }
              methodInput.value = method;
              let donaInput = form.querySelector('input[name="dona"]');
              if (!donaInput) { donaInput = document.createElement('input'); donaInput.type='hidden'; donaInput.name='dona'; donaInput.value='1'; form.appendChild(donaInput); }

              form.submit();
            });

        // Annulla ritorna alla selezione dei metodi
        const cancel = content.querySelector('.cancel-pay');
        if (cancel) cancel.addEventListener('click', function(){ showMethodSelection(Number(ov.querySelector('strong')?.textContent.replace(/[€\s]/g,'')||0)); });
      });
    });
  }

  // Intercettiamo il click su Dona ora e mostriamo il modal
  if (donaBtn && form) {
    donaBtn.addEventListener('click', function(e){
      const impEl = form.querySelector('input[name="importo"]');
      const importo = impEl ? Number(impEl.value) : 0;
      if (!importo || importo < 1) { alert('Inserisci un importo valido (minimo 1€).'); return; }
      showMethodSelection(importo);
    });
  }
})();

// --- Script cursore (esistente) ---
// Questo codice mantiene il cursore personalizzato già presente nel markup: il dot segue
// immediatamente la posizione del mouse mentre il ring lo insegue con interpolazione
const cur = document.getElementById('cursor'), ring = document.getElementById('cursorRing');
let mx = 0, my = 0, rx = 0, ry = 0;
document.addEventListener('mousemove', e => {
  mx = e.clientX; my = e.clientY;
  // aggiorniamo la posizione del punto (cursor)
  cur.style.cssText = `left:${mx}px;top:${my}px`;
});
(function loop(){
  // interpoliamo la posizione del ring per ottenere un effetto di ritardo/smooth
  rx += (mx - rx) * 0.12;
  ry += (my - ry) * 0.12;
  ring.style.cssText = `left:${rx}px;top:${ry}px`;
  requestAnimationFrame(loop);
})();
</script>
</body>
</html>