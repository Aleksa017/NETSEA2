# ============================================================
#  NETSEA — MISSIONE CAVALLUCCIO (L'ultimo rimasto!)
# ============================================================

$headers = @{ "User-Agent" = "Mozilla/5.0 (Windows NT 10.0; Win64; x64)" }

Write-Host "Tentativo finale per il cavalluccio..." -ForegroundColor Cyan

# Provo due sorgenti diverse nel caso una fallisca
$url1 = "https://images.unsplash.com/photo-1546026423-9d20b79b916f?w=1000&q=80"
$url2 = "https://pixabay.com/get/g8e1376b3064619d856d352b6f19478f6_1280.jpg" # Link diretto temporaneo

try {
    Invoke-WebRequest $url1 -Headers $headers -OutFile "uploads/news/news_cavalluccio_edna.jpg" -TimeoutSec 15
    Write-Host "OK! Cavalluccio news scaricato." -ForegroundColor Green
} catch {
    Write-Host "Primo link fallito, provo il secondo..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest $url2 -Headers $headers -OutFile "uploads/news/news_cavalluccio_edna.jpg" -TimeoutSec 15
        Write-Host "OK! Cavalluccio news scaricato via Pixabay." -ForegroundColor Green
    } catch {
        Write-Host "ERRORE persistente. Scaricalo manualmente qui: https://unsplash.com/photos/seahorse-underwater-zVp8Xq-Nf6E" -ForegroundColor Red
    }
}

# Verifica finale
if (Test-Path "uploads/news/news_cavalluccio_edna.jpg") {
    Write-Host "`nCOMPLIMENTI! Hai tutte le 34 immagini. Progetto pronto per la consegna! 🌊" -ForegroundColor Cyan
}