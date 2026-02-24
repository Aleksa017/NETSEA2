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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
      *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
      :root{
        --ink:#04111e;--deep:#071e33;--ocean:#0b3d5e;--wave:#1b9fd4;--foam:#72d7f0;--pearl:#e8f6fc;
        --text:#c5e4f5;--muted:#5d9ab8;--kelp:#2cb89b;--coral:#e05a3a;--ease:cubic-bezier(.25,.46,.45,.94);
      }
      body{font-family:'Outfit',sans-serif;background:var(--ink);color:var(--text);padding:2rem 2.5rem;}
      h1{font-family:'Cormorant Garamond',serif;font-size:2.2rem;color:var(--pearl);margin-bottom:1.5rem;}
      h2{font-family:'Cormorant Garamond',serif;font-size:1.5rem;color:var(--foam);margin-top:2rem;margin-bottom:.8rem;}
      a{color:var(--wave);text-decoration:none;transition:color .2s;}
      a:hover{color:var(--foam);}
      table{width:100%;border-collapse:collapse;margin:1.5rem 0;}
      th,td{padding:1rem;text-align:left;border-bottom:1px solid rgba(114,215,240,.15);}
      th{background:rgba(11,61,94,.2);color:var(--foam);font-weight:600;}
      tr:hover{background:rgba(27,159,212,.08);}
      .alert{padding:1rem;margin:.5rem 0;border-radius:10px;}
      .alert-success{background:rgba(44,184,155,.1);border:1px solid rgba(44,184,155,.3);color:#3dd4ae;}
      .alert-error{background:rgba(224,90,58,.1);border:1px solid rgba(224,90,58,.3);color:#e8836a;}
      .btn{display:inline-block;padding:.7rem 1.2rem;border-radius:8px;font-weight:600;cursor:pointer;border:none;transition:all .2s;}
      .btn-approve{background:var(--kelp);color:var(--ink);}
      .btn-approve:hover{background:#3dd4ae;transform:translateY(-2px);}
      .btn-back{background:rgba(114,215,240,.1);border:1px solid rgba(114,215,240,.2);color:var(--foam);}
      .btn-back:hover{background:rgba(114,215,240,.2);}
      .container{max-width:1000px;}
    </style>
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
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Qualifica</th>
                            <th>Ente</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($richieste as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['nome'] . " " . $r['cognome']) ?></td>
                                <td><?= htmlspecialchars($r['qualifica_dichiarata']) ?></td>
                                <td><?= htmlspecialchars($r['ente_dichiarato'] ?? 'N/D') ?></td>
                                <td>
                                    <a href="admin.php?approva=<?= $r['id_richiesta'] ?>" class="btn btn-approve" onclick="return confirm('Approvare questo ricercatore?')">✓ Approva</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div style="margin-top:2rem;">
            <a href="index.php" class="btn btn-back">← Torna alla Home</a>
        </div>
    </div>
</body>
</html>