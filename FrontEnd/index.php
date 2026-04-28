<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Neon Core</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .navbar-custom .navbar-text {
            color: #333 !important;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        body:not([data-theme="light"]) .navbar-custom .navbar-text {
            color: #ffffff !important;
        }
        .usura-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .progress-vertical {
            width: 35px;
            height: 160px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.4);
            transition: all 0.3s ease;
        }
        [data-theme="light"] .progress-vertical {
            border: 2px solid rgba(0,0,0,0.3);
            background: rgba(0,0,0,0.05);
        }
        .progress-bar-fill {
            position: absolute;
            bottom: 0;
            width: 100%;
            background: linear-gradient(to top, #00fa9a, #ffd700, #ff4d4d);
            transition: height 0.6s ease;
        }
        @keyframes pulse-critical {
            0%   { box-shadow: 0 0 0 0    rgba(255,77,77,0.7); }
            70%  { box-shadow: 0 0 0 15px rgba(255,77,77,0);   }
            100% { box-shadow: 0 0 0 0    rgba(255,77,77,0);   }
        }
        .usura-critica {
            animation: pulse-critical 1.5s infinite;
            border-color: #ff4d4d !important;
        }
        .alert-critical-banner {
            display: none;
            background-color: #ff4d4d;
            color: white;
            border: none;
            font-weight: bold;
        }
        #connStatus {
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: 12px;
            vertical-align: middle;
        }
        .conn-ok      { background: #00fa9a22; color: #00fa9a; border: 1px solid #00fa9a55; }
        .conn-waiting { background: #ffd70022; color: #ffd700; border: 1px solid #ffd70055; }
        .conn-error   { background: #ff4d4d22; color: #ff4d4d; border: 1px solid #ff4d4d55; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom py-3">
    <div class="container-fluid">
        <span class="navbar-text ms-2 fw-bold">
            Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>
            <span id="connStatus" class="conn-waiting">
                <i class="fa-solid fa-circle-notch fa-spin me-1"></i>Connessione…
            </span>
        </span>
        <div class="ms-auto">
            <a class="nav-link" href="logout.php">
                <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="dashboard-container">

    <!-- ① Banner usura critica (≥85%) -->
    <div id="usuraAlert"
         class="alert alert-danger alert-critical-banner d-flex align-items-center mb-3"
         role="alert">
        <i class="fa-solid fa-triangle-exclamation me-3 fa-lg"></i>
        <div>LIVELLO USURA CRITICO: Il sistema richiede manutenzione immediata (&gt;85%).</div>
    </div>

    <!-- ② Banner pausa -->
    <div id="stopBanner"
         class="alert alert-warning alert-custom d-flex align-items-center"
         role="alert" style="display:none;">
        <i class="fa-solid fa-circle-exclamation me-3"></i>
        <div><strong>Sistema In Pausa:</strong> La ricezione dei dati è sospesa.</div>
    </div>

    <!-- ③ Banner errore ThingsBoard -->
    <div id="errorBanner"
         class="alert alert-danger alert-custom d-flex align-items-center"
         role="alert" style="display:none;">
        <i class="fa-solid fa-triangle-exclamation me-3"></i>
        <div id="errorBannerText">Errore di comunicazione con ThingsBoard.</div>
    </div>

    <div class="row g-4 mb-3">

        <!-- Filtri tempo -->
        <div class="col-md-2">
            <div class="custom-card">
                <ul class="filter-list" id="timeFilters">
                    <li class="active-filter" onclick="changeTimeFormat('minuto', this)">Per minuto</li>
                    <li onclick="changeTimeFormat('ora', this)">Per ora</li>
                </ul>
            </div>
        </div>

        <!-- Filtri colore -->
        <div class="col-md-2">
            <div class="custom-card">
                <ul class="filter-list" id="colorFilters">
                    <li class="active-filter" onclick="toggleColor(1, this)"><span class="color-dot dot-giallo"></span>Giallo</li>
                    <li class="active-filter" onclick="toggleColor(2, this)"><span class="color-dot dot-verde"></span>Verde</li>
                    <li class="active-filter" onclick="toggleColor(3, this)"><span class="color-dot dot-rosa"></span>Rosa</li>
                    <li class="active-filter" onclick="toggleColor(0, this)"><span class="color-dot dot-blu"></span>Blu</li>
                </ul>
            </div>
        </div>

        <!-- Grafico produzione -->
        <div class="col-md-8">
            <div class="custom-card" style="position:relative; height:250px;">
                <canvas id="productionChart"></canvas>
            </div>
        </div>

    </div>

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between">
            <div>
                <button id="toggleBtn" class="btn btn-custom me-2" onclick="toggleData(this)">
                    <i class="fa-solid fa-stop me-2"></i>Stop
                </button>
                <button class="btn btn-custom" onclick="clearLog()">
                    <i class="fa-solid fa-trash me-2"></i>Cancella log
                </button>
            </div>
            <button class="btn btn-custom rounded-circle p-2 px-3" onclick="toggleTheme()">
                <i id="themeIcon" class="fa-solid fa-sun"></i>
            </button>
        </div>
    </div>

    <div class="row g-4">

        <!-- Tabella log -->
        <div class="col-md-7">
            <div class="custom-card" style="max-height:300px; overflow-y:auto;">
                <table class="table table-transparent">
                    <thead>
                    <tr>
                        <th>Colore prodotto</th>
                        <th>Data</th>
                        <th>Ora</th>
                        <th title="Tempo tra questo prodotto e il precedente">Δt ciclo</th>
                    </tr>
                    </thead>
                    <tbody id="logTableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Media + info robot -->
        <div class="col-md-3">
            <div class="custom-card d-flex flex-column align-items-center justify-content-center gap-3">
                <h4 class="mb-0 fw-bold text-center" id="mediaValue">In attesa dati…</h4>
                <div class="text-center" style="opacity:0.75; font-size:0.9rem; line-height:1.7;">
                    <div>Ultimo Δt:&nbsp;<strong id="lastDelta">—</strong></div>
                    <div>Tempo robot:&nbsp;<strong id="tempoValue">—</strong></div>
                </div>
            </div>
        </div>

        <!-- Usura -->
        <div class="col-md-2">
            <div class="custom-card usura-container">
                <h6 class="fw-bold mb-2">Usura: <span id="usuraPercentText">0.0%</span></h6>
                <div id="usuraBorder" class="progress-vertical">
                    <div id="usuraFill" class="progress-bar-fill" style="height:0%;"></div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════════
//  COSTANTI — ADATTA ALLA VELOCITÀ REALE DEL TUO ROBOT
// ═══════════════════════════════════════════════════════════════════

/** Δt atteso per un robot in ottima salute (ms). Sotto questa soglia usura = 0%. */
const TEMPO_IDEALE_MS   = 3_000;   // 3 secondi

/** Δt oltre il quale la macchina è considerata completamente usurata (ms). */
const TEMPO_ESAURITO_MS = 120_000; // 120 secondi

/** Incremento massimo di usura per singolo prodotto (%). Garantisce salita graduale. */
const USURA_STEP_MAX = 3.0;

/** Intervallo polling ThingsBoard (ms). */
const POLL_MS = 3_000;

// ═══════════════════════════════════════════════════════════════════
//  STATO GLOBALE
// ═══════════════════════════════════════════════════════════════════
let currentUsura  = 0.0;   // Usura accumulata (solo crescente, 0–100)
let currentFormat = 'minuto';
let isStopped     = false;

// Timestamp (ms) dell'ultimo color_sensor_event confermato
// Viene confrontato con quello nuovo per calcolare il Δt di ciclo
let prevColorTs = 0;

// Ultimo ts ricevuto per ogni device (evita di riprocessare lo stesso dato)
let lastColorTs = 0;
let lastRilevTs = 0;
let lastTempoTs = 0;

// Contatori eventi per la finestra grafico in corso
// indice dataset: 0=Blu  1=Giallo  2=Verde  3=Rosa
let windowCounts = { 0: 0, 1: 0, 2: 0, 3: 0 };
let windowStartMs = Date.now();
let labelTime     = 0;

// Contatori per la media produzione (da dispositivo Rilevazione)
let rilevCount   = 0;
let rilevWinMs   = Date.now();

// ═══════════════════════════════════════════════════════════════════
//  MAPPING  color_sensor_event → colore dashboard
// ═══════════════════════════════════════════════════════════════════
const COLOR_MAP = {
    'blue':  { idx: 0, nome: 'Blu',    classe: 'dot-blu'    },
    'green': { idx: 2, nome: 'Verde',  classe: 'dot-verde'  },
    'red':   { idx: 3, nome: 'Rosa',   classe: 'dot-rosa'   },
    'error': { idx: 1, nome: 'Errore', classe: 'dot-giallo' }
};

// ═══════════════════════════════════════════════════════════════════
//  GRAFICO
// ═══════════════════════════════════════════════════════════════════
const ctx = document.getElementById('productionChart').getContext('2d');
const productionChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            { label: 'Blu',    data: [], borderColor: '#1e90ff', tension: 0.3, borderWidth: 2 },
            { label: 'Giallo', data: [], borderColor: '#ffd700', tension: 0.3, borderWidth: 2 },
            { label: 'Verde',  data: [], borderColor: '#00fa9a', tension: 0.3, borderWidth: 2 },
            { label: 'Rosa',   data: [], borderColor: '#ff69b4', tension: 0.3, borderWidth: 2 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 300 },
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(128,128,128,0.1)' },
                border: { display: false },
                ticks: { precision: 0 }
            },
            x: { grid: { display: false }, border: { display: false } }
        }
    }
});

/** Crea il primo punto (vuoto) all'avvio. */
function initChart() {
    labelTime = 1;
    productionChart.data.labels.push(currentFormat === 'minuto' ? '+1m' : '+1h');
    productionChart.data.datasets.forEach(d => d.data.push(0));
    productionChart.update('none');
}

/**
 * Aggiorna il punto CORRENTE (ultimo) del grafico con i contatori
 * della finestra in corso. Chiamato ad ogni poll per effetto live.
 */
function aggiornaPuntoCorrente() {
    const len = productionChart.data.labels.length;
    if (len === 0) return;
    productionChart.data.datasets.forEach((ds, i) => {
        ds.data[len - 1] = windowCounts[i] || 0;
    });
    productionChart.update('none');
}

/**
 * Chiude la finestra corrente del grafico e ne apre una nuova:
 * il punto attuale viene "congelato" con i valori definitivi,
 * poi si aggiunge un nuovo punto a zero per la finestra successiva.
 */
function avanzaFinestraGrafico() {
    labelTime++;
    const suffix = currentFormat === 'minuto' ? 'm' : 'h';
    productionChart.data.labels.push(`+${labelTime}${suffix}`);
    productionChart.data.datasets.forEach(d => d.data.push(0));

    if (productionChart.data.labels.length > 10) {
        productionChart.data.labels.shift();
        productionChart.data.datasets.forEach(d => d.data.shift());
    }

    windowCounts  = { 0: 0, 1: 0, 2: 0, 3: 0 };
    windowStartMs = Date.now();
    productionChart.update();
}

// ═══════════════════════════════════════════════════════════════════
//  CALCOLO USURA  (basato sul Δt tra prodotti consecutivi)
// ═══════════════════════════════════════════════════════════════════
/**
 * Viene chiamata ogni volta che arriva un nuovo color_sensor_event.
 *
 * Logica:
 *  1. Calcola Δt = timestamp_corrente − timestamp_prodotto_precedente
 *  2. Mappa il Δt su [0%, 100%] usando TEMPO_IDEALE e TEMPO_ESAURITO
 *  3. Aggiorna currentUsura SOLO SE il valore calcolato è superiore
 *     (usura è irreversibile), con step massimo USURA_STEP_MAX per
 *     garantire una salita graduale visivamente convincente
 *
 * @param {number} tsCurrent  Timestamp ms del prodotto appena rilevato
 */
function aggiornaUsura(tsCurrent) {
    // Prima rilevazione: salvo solo il timestamp, non calcolo ancora
    if (prevColorTs === 0) {
        prevColorTs = tsCurrent;
        return;
    }

    const deltaMs = tsCurrent - prevColorTs;
    prevColorTs   = tsCurrent;

    // Timestamp anomalo (clock skew, dati fuori ordine)
    if (deltaMs <= 0) return;

    // Mostra il Δt di ciclo nell'interfaccia
    document.getElementById('lastDelta').textContent = (deltaMs / 1000).toFixed(2) + ' s';

    // ──────────────────────────────────────────────────────
    //  FORMULA USURA
    //
    //  usuraTarget = ((Δt − IDEALE) / (ESAURITO − IDEALE)) × 100
    //
    //  Esempi con default (IDEALE=3s, ESAURITO=120s):
    //    Δt =   3 s  →  0%
    //    Δt =  30 s  → ~23%
    //    Δt =  60 s  → ~48%
    //    Δt = 120 s  → 100%
    // ──────────────────────────────────────────────────────
    const usuraTarget = Math.max(0, Math.min(100,
        ((deltaMs - TEMPO_IDEALE_MS) / (TEMPO_ESAURITO_MS - TEMPO_IDEALE_MS)) * 100
    ));

    // L'usura sale gradualmente (max USURA_STEP_MAX % per evento)
    // e NON scende mai (wear è fisicamente irreversibile)
    if (usuraTarget > currentUsura) {
        const passo  = Math.min(USURA_STEP_MAX, usuraTarget - currentUsura);
        currentUsura = Math.min(100, currentUsura + passo);
        updateUsuraDisplay(currentUsura);
    }
}

/** Aggiorna barra, testo e banner critico (≥85%). */
function updateUsuraDisplay(valore) {
    const perc = valore.toFixed(1);
    document.getElementById('usuraPercentText').textContent = perc + '%';
    document.getElementById('usuraFill').style.height       = perc + '%';

    const border = document.getElementById('usuraBorder');
    const banner = document.getElementById('usuraAlert');

    if (valore >= 85) {
        border.classList.add('usura-critica');
        banner.style.display = 'flex';
    } else {
        border.classList.remove('usura-critica');
        banner.style.display = 'none';
    }
}

// ═══════════════════════════════════════════════════════════════════
//  GESTIONE EVENTO COLORE
// ═══════════════════════════════════════════════════════════════════
function processColorEvent(rawValue, tsMs) {
    const key    = (rawValue ?? '').toLowerCase().trim();
    const mapped = COLOR_MAP[key] ?? { idx: 0, nome: rawValue, classe: 'dot-blu' };

    // Calcola Δt per colonna log (PRIMA di aggiornare prevColorTs in aggiornaUsura)
    const deltaDisplay = prevColorTs > 0
        ? ((tsMs - prevColorTs) / 1000).toFixed(2) + ' s'
        : '—';

    // Aggiunge riga al log
    const d      = new Date(tsMs);
    const tbody  = document.getElementById('logTableBody');
    const tr     = document.createElement('tr');
    tr.innerHTML =
        `<td><span class="color-dot ${mapped.classe}"></span>${mapped.nome}</td>` +
        `<td>${d.toLocaleDateString('it-IT')}</td>`                               +
        `<td>${d.toLocaleTimeString('it-IT')}</td>`                               +
        `<td>${deltaDisplay}</td>`;
    tbody.insertBefore(tr, tbody.firstChild);
    if (tbody.children.length > 8) tbody.removeChild(tbody.lastChild);

    // Incrementa il contatore della finestra grafico corrente
    windowCounts[mapped.idx] = (windowCounts[mapped.idx] || 0) + 1;

    // Calcola e aggiorna usura (aggiorna anche prevColorTs internamente)
    aggiornaUsura(tsMs);
}

// ═══════════════════════════════════════════════════════════════════
//  FETCH DATI DA THINGSBOARD  (proxy PHP → thingsboard_api.php)
// ═══════════════════════════════════════════════════════════════════
async function fetchTbData() {
    if (isStopped) return;

    try {
        const resp = await fetch('thingsboard_api.php');

        if (resp.status === 401) { setConnStatus('error', 'Sessione scaduta'); return; }
        if (!resp.ok)            { setConnStatus('error', `Errore HTTP ${resp.status}`); return; }

        const data = await resp.json();

        if (data.status !== 'ok') {
            setConnStatus('error', data.message ?? 'Errore TB');
            showErrorBanner(data.message ?? 'Errore di comunicazione con ThingsBoard.');
            return;
        }

        hideErrorBanner();
        setConnStatus('ok', 'Live');

        // ── Terzo Robot → Colore ─────────────────────────────────
        const colDev = data.devices.colore;
        if (colDev?.color_sensor_event?.length) {
            const ev = colDev.color_sensor_event[0];
            if (ev.ts > lastColorTs) {
                lastColorTs = ev.ts;
                processColorEvent(ev.value, ev.ts);
            }
        }

        // ── Secondo Robot → Rilevazione (infrared) ──────────────
        const rilDev = data.devices.rilevazione;
        if (rilDev?.infrared_sensor_event?.length) {
            const ev = rilDev.infrared_sensor_event[0];
            if (ev.ts > lastRilevTs) {
                lastRilevTs = ev.ts;
                if (ev.value === true || ev.value === 'true') rilevCount++;
            }
        }

        // ── Primo Robot → Tempo di Esecuzione ────────────────────
        const tmpDev = data.devices.tempo;
        if (tmpDev?.time?.length) {
            const ev = tmpDev.time[0];
            if (ev.ts > lastTempoTs) {
                lastTempoTs = ev.ts;
                const secs = parseFloat(ev.value);
                if (!isNaN(secs))
                    document.getElementById('tempoValue').textContent = secs.toFixed(2) + ' s';
            }
        }

        // ── Aggiorna punto corrente del grafico (effetto live) ───
        aggiornaPuntoCorrente();

        // ── Avanza finestra se scaduta ───────────────────────────
        const windowMs = currentFormat === 'minuto' ? 60_000 : 3_600_000;
        if (Date.now() - windowStartMs >= windowMs) avanzaFinestraGrafico();

        // ── Ricalcola media ogni minuto reale ────────────────────
        const minPassati = (Date.now() - rilevWinMs) / 60_000;
        if (minPassati >= 1) {
            const rate = Math.round(rilevCount / minPassati);
            document.getElementById('mediaValue').textContent =
                currentFormat === 'minuto'
                    ? `Media al min: ${rate} p`
                    : `Media oraria: ${Math.round(rate * 60)} p`;
            rilevCount = 0;
            rilevWinMs = Date.now();
        }

    } catch (e) {
        setConnStatus('error', 'Connessione persa');
        console.error('fetchTbData:', e);
    }
}

// ═══════════════════════════════════════════════════════════════════
//  UTILITY UI
// ═══════════════════════════════════════════════════════════════════
function setConnStatus(type, text) {
    const el = document.getElementById('connStatus');
    const icon = {
        ok:      '<i class="fa-solid fa-circle me-1" style="font-size:.55rem"></i>',
        waiting: '<i class="fa-solid fa-circle-notch fa-spin me-1"></i>',
        error:   '<i class="fa-solid fa-triangle-exclamation me-1"></i>'
    };
    el.className = `conn-${type}`;
    el.innerHTML = (icon[type] ?? '') + text;
}

function showErrorBanner(msg) {
    document.getElementById('errorBanner').style.display   = 'flex';
    document.getElementById('errorBannerText').textContent = msg;
}
function hideErrorBanner() {
    document.getElementById('errorBanner').style.display = 'none';
}

function toggleTheme() {
    const body = document.body;
    const icon = document.getElementById('themeIcon');
    if (body.getAttribute('data-theme') === 'light') {
        body.setAttribute('data-theme', 'dark');
        icon.className = 'fa-solid fa-moon';
    } else {
        body.setAttribute('data-theme', 'light');
        icon.className = 'fa-solid fa-sun';
    }
}

function toggleColor(id, el) {
    el.classList.toggle('active-filter');
    productionChart.data.datasets[id].hidden = !el.classList.contains('active-filter');
    productionChart.update();
}

let pollInterval = setInterval(fetchTbData, POLL_MS);

function toggleData(btn) {
    if (!isStopped) {
        clearInterval(pollInterval);
        document.getElementById('stopBanner').style.display = 'flex';
        btn.innerHTML = '<i class="fa-solid fa-play me-2"></i>Riprendi';
        isStopped     = true;
    } else {
        pollInterval = setInterval(fetchTbData, POLL_MS);
        document.getElementById('stopBanner').style.display = 'none';
        btn.innerHTML = '<i class="fa-solid fa-stop me-2"></i>Stop';
        isStopped     = false;
        fetchTbData();
    }
}

function changeTimeFormat(f, el) {
    document.querySelectorAll('#timeFilters li').forEach(li => li.classList.remove('active-filter'));
    el.classList.add('active-filter');
    currentFormat = f;
    labelTime     = 1;
    windowCounts  = { 0: 0, 1: 0, 2: 0, 3: 0 };
    windowStartMs = Date.now();
    productionChart.data.labels   = [f === 'minuto' ? '+1m' : '+1h'];
    productionChart.data.datasets.forEach(d => d.data = [0]);
    productionChart.update();
}

function clearLog() {
    document.getElementById('logTableBody').innerHTML = '';
}

// ═══════════════════════════════════════════════════════════════════
//  AVVIO
// ═══════════════════════════════════════════════════════════════════
initChart();    // crea il primo punto vuoto del grafico
fetchTbData();  // prima chiamata immediata, senza aspettare i 3 s
</script>
</body>
</html>