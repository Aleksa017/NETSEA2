<?php
require 'config.php';

// Verifica se l'utente è loggato ed è un admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("Accesso negato. Questa pagina è riservata agli amministratori.");
}

// AZIONE DI APPROVAZIONE
if (isset($_GET['approva'])) {
    $id_req = $_GET['approva'];
    
    // 1. Recupero i dati della richiesta
    $stmt = $connessione->prepare("SELECT id_utente, qualifica_dichiarata FROM Richiesta_Ricercatore WHERE id_richiesta = ?");
    $stmt->execute([$id_req]);
    $req = $stmt->fetch();

    if ($req) {
        try {
            $connessione->beginTransaction();

            // CONTROLLO PREVENTIVO: l'utente è già un ricercatore?
            $check = $connessione->prepare("SELECT 1 FROM Ricercatore WHERE id_ricercatore = ?");
            $check->execute([$req['id_utente']]);
            
            if (!$check->fetch()) {
                // 2. Inserisco SOLO SE non esiste già
                $ins = $connessione->prepare("INSERT INTO Ricercatore (id_ricercatore, qualifica) VALUES (?, ?)");
                $ins->execute([$req['id_utente'], $req['qualifica_dichiarata']]);
            }

            // 3. Aggiorno lo stato della richiesta in ogni caso per toglierla dalla lista
            $upd = $connessione->prepare("UPDATE Richiesta_Ricercatore SET stato = 'approvato' WHERE id_richiesta = ?");
            $upd->execute([$id_req]);

// Recuperiamo l'email dell'utente per scrivergli
$stEmail = $connessione->prepare("SELECT email FROM Utente WHERE id_utente = ?");
$stEmail->execute([$req['id_utente']]);
$userEmail = $stEmail->fetchColumn();

// Esempio logico (richiede PHPMailer per funzionare davvero)
$messaggio_mail = "Ciao, il tuo profilo ricercatore è stato approvato! Ora puoi pubblicare contenuti.";
// mail($userEmail, "Profilo Approvato", $messaggio_mail);

            $connessione->commit();
            
            // REINDIRIZZAMENTO: evita l'errore al refresh della pagina
            header("Location: admin.php?success=1");
            exit();

        } catch (Exception $e) {
            $connessione->rollBack();
            echo "<p style='color:red;'>Errore durante l'approvazione: " . $e->getMessage() . "</p>";
        }
    }
}

// AZIONE DI RIFIUTO
if (isset($_GET['rifiuta'])) {
    $id_req = (int)$_GET['rifiuta'];
    $connessione->prepare("UPDATE Richiesta_Ricercatore SET stato = 'rifiutato' WHERE id_richiesta = ?")->execute([$id_req]);
    header("Location: admin.php?success=rifiutato");
    exit();
}

// flash messages da mostrare in pagina
$flash = [];
if (isset($_GET['success'])) {
    $flash[] = "Operazione completata con successo!";
} // rimosso flash news_success perché la gestione news avviene altrove


// Mostra la lista delle richieste pendenti
$richieste = $connessione->query("
    SELECT r.*, u.nome, u.cognome 
    FROM Richiesta_Ricercatore r 
    JOIN Utente u ON r.id_utente = u.id_utente 
    WHERE r.stato = 'in_attesa'
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Admin — NetSea</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php foreach($flash as $msg): ?>
            <p class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></p>
        <?php endforeach; ?>
        <h1>⚙️ Gestione Richieste Ricercatori</h1>
        
        <?php if (empty($richieste)): ?>
            <p style="color:var(--muted);margin:1.5rem 0;">Nessuna richiesta in attesa di approvazione.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <div style="display:flex;flex-direction:column;gap:1.5rem;">
                <?php foreach($richieste as $r): ?>
                <div style="background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.15);border-radius:14px;padding:1.5rem;">

                  <!-- Intestazione richiedente -->
                  <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
                    <div>
                      <p style="color:var(--pearl);font-size:1.1rem;font-weight:500;margin-bottom:.25rem;">
                        👤 <?= htmlspecialchars($r['nome'] . ' ' . $r['cognome']) ?>
                      </p>
                      <p style="color:var(--wave);font-size:.82rem;">🎓 <?= htmlspecialchars($r['qualifica_dichiarata'] ?? 'N/D') ?></p>
                      <p style="color:var(--muted);font-size:.82rem;">🏛️ <?= htmlspecialchars($r['ente_dichiarato'] ?? 'N/D') ?></p>
                      <?php if (!empty($r['motivazione'])): ?>
                      <p style="color:rgba(197,228,245,.7);font-size:.8rem;margin-top:.5rem;font-style:italic;">
                        "<?= htmlspecialchars(mb_substr($r['motivazione'], 0, 200)) ?><?= mb_strlen($r['motivazione'])>200?'…':'' ?>"
                      </p>
                      <?php endif; ?>
                      <p style="color:rgba(114,215,240,.4);font-size:.72rem;margin-top:.4rem;">
                        Richiesta #<?= $r['id_richiesta'] ?>
                        <?= $r['data_richiesta'] ? ' · ' . date('d M Y', strtotime($r['data_richiesta'])) : '' ?>
                      </p>
                    </div>
                  </div>

                  <!-- File allegati -->
                  <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem;">
                    <?php if (!empty($r['certificato_path'])): ?>
                    <a href="<?= htmlspecialchars($r['certificato_path']) ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:rgba(27,159,212,.1);border:1px solid rgba(27,159,212,.25);border-radius:8px;color:var(--wave);text-decoration:none;font-size:.82rem;transition:background .2s;"
                       onmouseover="this.style.background='rgba(27,159,212,.2)'" onmouseout="this.style.background='rgba(27,159,212,.1)'">
                      📄 Certificato / Documento
                    </a>
                    <?php else: ?>
                    <span style="font-size:.78rem;color:var(--muted);padding:.5rem .75rem;border:1px solid rgba(114,215,240,.08);border-radius:8px;">📄 Nessun certificato allegato</span>
                    <?php endif; ?>

                    <?php if (!empty($r['badge_path'])): ?>
                    <a href="<?= htmlspecialchars($r['badge_path']) ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:rgba(44,184,155,.1);border:1px solid rgba(44,184,155,.25);border-radius:8px;color:#2cb89b;text-decoration:none;font-size:.82rem;transition:background .2s;"
                       onmouseover="this.style.background='rgba(44,184,155,.2)'" onmouseout="this.style.background='rgba(44,184,155,.1)'">
                      🪪 Badge / Tessera
                    </a>
                    <?php else: ?>
                    <span style="font-size:.78rem;color:var(--muted);padding:.5rem .75rem;border:1px solid rgba(114,215,240,.08);border-radius:8px;">🪪 Nessun badge allegato</span>
                    <?php endif; ?>
                  </div>

                  <!-- Bottoni azione -->
                  <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <a href="admin.php?approva=<?= $r['id_richiesta'] ?>"
                       onclick="return confirm('Approvare <?= htmlspecialchars(addslashes($r['nome'].' '.$r['cognome'])) ?>?')"
                       style="display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.4rem;background:rgba(44,184,155,.15);border:1px solid rgba(44,184,155,.35);border-radius:8px;color:#2cb89b;text-decoration:none;font-size:.85rem;font-weight:600;transition:background .2s;"
                       onmouseover="this.style.background='rgba(44,184,155,.28)'" onmouseout="this.style.background='rgba(44,184,155,.15)'">
                      ✓ Approva
                    </a>
                    <a href="admin.php?rifiuta=<?= $r['id_richiesta'] ?>"
                       onclick="return confirm('Rifiutare la richiesta di <?= htmlspecialchars(addslashes($r['nome'].' '.$r['cognome'])) ?>?')"
                       style="display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.4rem;background:rgba(232,131,106,.1);border:1px solid rgba(232,131,106,.3);border-radius:8px;color:#e8836a;text-decoration:none;font-size:.85rem;font-weight:600;transition:background .2s;"
                       onmouseover="this.style.background='rgba(232,131,106,.22)'" onmouseout="this.style.background='rgba(232,131,106,.1)'">
                      ✕ Rifiuta
                    </a>
                  </div>

                </div>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="margin-top:2rem;">
            <a href="index.php" class="btn btn-back">← Torna alla Home</a>
        </div>
    </div>
</body>
</html>