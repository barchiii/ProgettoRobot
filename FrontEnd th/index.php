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

    <div id="usuraAlert"
         class="alert alert-danger alert-critical-banner d-none align-items-center mb-3"
         role="alert">
        <i class="fa-solid fa-triangle-exclamation me-3 fa-lg"></i>
        <div>LIVELLO USURA CRITICO: Il sistema richiede manutenzione immediata (&gt;85%).</div>
    </div>

    <div id="stopBanner"
         class="alert alert-warning alert-custom d-none align-items-center"
         role="alert">
        <i class="fa-solid fa-circle-exclamation me-3"></i>
        <div><strong>Sistema In Pausa:</strong> La ricezione dei dati è sospesa.</div>
    </div>

    <div id="errorBanner"
         class="alert alert-danger alert-custom d-none align-items-center"
         role="alert">
        <i class="fa-solid fa-triangle-exclamation me-3"></i>
        <div id="errorBannerText">Errore di comunicazione con ThingsBoard.</div>
    </div>

    <div class="row g-4 mb-3">

        <div class="col-md-2">
            <div class="custom-card">
                <ul class="filter-list" id="timeFilters">
                    <li class="active-filter" onclick="changeTimeFormat('minuto', this)">Per minuto</li>
                    <li onclick="changeTimeFormat('ora', this)">Per ora</li>
                </ul>
            </div>
        </div>

        <div class="col-md-2">
            <div class="custom-card">
                <ul class="filter-list" id="colorFilters">
                    <li class="active-filter" onclick="toggleColor(1, this)"><span class="color-dot dot-giallo"></span>Giallo</li>
                    <li class="active-filter" onclick="toggleColor(2, this)"><span class="color-dot dot-verde"></span>Verde</li>
                    <li class="active-filter" onclick="toggleColor(3, this)"><span class="color-dot dot-rosso"></span>Rosso</li>
                    <li class="active-filter" onclick="toggleColor(0, this)"><span class="color-dot dot-blu"></span>Blu</li>
                </ul>
            </div>
        </div>

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

        <div class="col-md-3">
            <div class="custom-card d-flex flex-column align-items-center justify-content-center gap-3">
                <h4 class="mb-0 fw-bold text-center" id="mediaValue">In attesa dati…</h4>
                <div class="text-center" style="opacity:0.75; font-size:0.9rem; line-height:1.7;">
                    <div>Ultimo Δt:&nbsp;<strong id="lastDelta">—</strong></div>
                    <div>Tempo robot:&nbsp;<strong id="tempoValue">—</strong></div>
                </div>
            </div>
        </div>

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
    //  COSTANTI
    // ═══════════════════════════════════════════════════════════════════
    const TEMPO_IDEALE_MS   = 3000;
    const TEMPO_ESAURITO_MS = 120000;
    const USURA_STEP_MAX = 3.0;
    const POLL_MS = 3000;

    // ═══════════════════════════════════════════════════════════════════
    //  STATO GLOBALE
    // ═══════════════════════════════════════════════════════════════════
    let currentUsura  = 0.0;
    let currentFormat = 'minuto';
    let isStopped     = false;
    let prevColorTs = 0;
    let lastColorTs = 0;
    let lastRilevTs = 0;
    let lastTempoTs = 0;
    let firstFetch  = true; // al primo fetch memorizziamo i ts senza processare eventi
    let prevTsSnapshot = { colore: 0, rilev: 0, tempo: 0 }; // snapshot ts del ciclo precedente
    const LIVE_TIMEOUT_MS = 12000; // dopo 12 s senza nuovi ts → Inattivo
    let lastLiveMs = 0;
    let windowCounts = { 0: 0, 1: 0, 2: 0, 3: 0 };
    let windowStartMs = Date.now();
    let labelTime     = 0;
    let rilevCount   = 0;
    let rilevWinMs   = Date.now();

    const COLOR_MAP = {
        'blue':  { idx: 0, nome: 'Blu',    classe: 'dot-blu'    },
        'green': { idx: 2, nome: 'Verde',  classe: 'dot-verde'  },
        'red':   { idx: 3, nome: 'Rosa',   classe: 'dot-rosa'   },
        'error': { idx: 1, nome: 'Errore', classe: 'dot-giallo' }
    };

    const ctx = document.getElementById('productionChart').getContext('2d');
    const productionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'Blu',    data: [], borderColor: '#1e90ff', tension: 0.3, borderWidth: 2 },
                { label: 'Giallo', data: [], borderColor: '#ffd700', tension: 0.3, borderWidth: 2 },
                { label: 'Verde',  data: [], borderColor: '#00fa9a', tension: 0.3, borderWidth: 2 },
                { label: 'Rosso',  data: [], borderColor: '#ff0000', tension: 0.3, borderWidth: 2 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 300 },
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(128,128,128,0.1)' }, border: { display: false }, ticks: { precision: 0 } },
                x: { grid: { display: false }, border: { display: false } }
            }
        }
    });

    function initChart() {
        labelTime = 1;
        productionChart.data.labels.push(currentFormat === 'minuto' ? '+1m' : '+1h');
        productionChart.data.datasets.forEach(d => d.data.push(0));
        productionChart.update('none');
    }

    function aggiornaPuntoCorrente() {
        const len = productionChart.data.labels.length;
        if (len === 0) return;
        productionChart.data.datasets.forEach((ds, i) => {
            ds.data[len - 1] = windowCounts[i] || 0;
        });
        productionChart.update('none');
    }

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

    function aggiornaUsura(tsCurrent) {
        if (prevColorTs === 0) {
            prevColorTs = tsCurrent;
            return;
        }
        const deltaMs = tsCurrent - prevColorTs;
        prevColorTs   = tsCurrent;
        if (deltaMs <= 0) return;
        document.getElementById('lastDelta').textContent = (deltaMs / 1000).toFixed(2) + ' s';
        const usuraTarget = Math.max(0, Math.min(100, ((deltaMs - TEMPO_IDEALE_MS) / (TEMPO_ESAURITO_MS - TEMPO_IDEALE_MS)) * 100));
        if (usuraTarget > currentUsura) {
            const passo  = Math.min(USURA_STEP_MAX, usuraTarget - currentUsura);
            currentUsura = Math.min(100, currentUsura + passo);
            updateUsuraDisplay(currentUsura);
        }
    }

    function updateUsuraDisplay(valore) {
        const perc = valore.toFixed(1);
        document.getElementById('usuraPercentText').textContent = perc + '%';
        document.getElementById('usuraFill').style.height = perc + '%';

        const border = document.getElementById('usuraBorder');
        const banner = document.getElementById('usuraAlert');

        if (valore >= 85) {
            border.classList.add('usura-critica');
            banner.classList.remove('d-none');
            banner.classList.add('d-flex');
        } else {
            border.classList.remove('usura-critica');
            banner.classList.add('d-none');
            banner.classList.remove('d-flex');
        }
    }

    function processColorEvent(rawValue, tsMs) {
        const key    = (rawValue ?? '').toLowerCase().trim();
        const mapped = COLOR_MAP[key] ?? { idx: 0, nome: rawValue, classe: 'dot-blu' };
        const deltaDisplay = prevColorTs > 0 ? ((tsMs - prevColorTs) / 1000).toFixed(2) + ' s' : '—';
        const d      = new Date(tsMs);
        const tbody  = document.getElementById('logTableBody');
        const tr     = document.createElement('tr');
        tr.innerHTML = `<td><span class="color-dot ${mapped.classe}"></span>${mapped.nome}</td>` +
            `<td>${d.toLocaleDateString('it-IT')}</td>` +
            `<td>${d.toLocaleTimeString('it-IT')}</td>` +
            `<td>${deltaDisplay}</td>`;
        tbody.insertBefore(tr, tbody.firstChild);
        if (tbody.children.length > 8) tbody.removeChild(tbody.lastChild);
        windowCounts[mapped.idx] = (windowCounts[mapped.idx] || 0) + 1;
        aggiornaUsura(tsMs);
    }

    async function fetchTbData() {
        if (isStopped) return;
        try {
            const resp = await fetch('thingsboard_api.php');
            if (resp.status === 401) {
                setConnStatus('error', 'Sessione scaduta');
                return;
            }
            if (!resp.ok) {
                setConnStatus('error', `Errore HTTP ${resp.status}`);
                return;
            }
            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                setConnStatus('error', 'Risposta non valida');
                showErrorBanner('Il server non ha risposto in formato JSON.');
                return;
            }
            const data = await resp.json();
            if (data.status !== 'ok') {
                setConnStatus('error', data.message ?? 'Errore TB');
                showErrorBanner(data.message ?? 'Errore di comunicazione con ThingsBoard.');
                return;
            }
            hideErrorBanner();

            const colDev = data.devices.colore;
            if (colDev?.color_sensor_event?.length) {
                const ev = colDev.color_sensor_event[0];
                if (firstFetch) {
                    lastColorTs = ev.ts; // memorizziamo senza processare
                } else if (ev.ts > lastColorTs) {
                    lastColorTs = ev.ts;
                    processColorEvent(ev.value, ev.ts);
                }
            }
            const rilDev = data.devices.rilevazione;
            if (rilDev?.infrared_sensor_event?.length) {
                const ev = rilDev.infrared_sensor_event[0];
                if (firstFetch) {
                    lastRilevTs = ev.ts;
                } else if (ev.ts > lastRilevTs) {
                    lastRilevTs = ev.ts;
                    if (ev.value === true || ev.value === 'true') rilevCount++;
                }
            }
            const tmpDev = data.devices.tempo;
            if (tmpDev?.time?.length) {
                const ev = tmpDev.time[0];
                if (firstFetch) {
                    lastTempoTs = ev.ts;
                } else if (ev.ts > lastTempoTs) {
                    lastTempoTs = ev.ts;
                    let secs = parseFloat(ev.value);
                    // Se il valore è in millisecondi (>600), convertiamo in secondi
                    if (!isNaN(secs)) {
                        if (secs > 600) secs = secs / 1000;
                        document.getElementById('tempoValue').textContent = secs.toFixed(2) + ' s';
                    }
                }
            }
            firstFetch = false;

            // Live: almeno un ts è cambiato rispetto al ciclo precedente
            const anyNew = (
                (colDev?.color_sensor_event?.[0]?.ts  > prevTsSnapshot.colore)  ||
                (rilDev?.infrared_sensor_event?.[0]?.ts > prevTsSnapshot.rilev)  ||
                (tmpDev?.time?.[0]?.ts                  > prevTsSnapshot.tempo)
            );
            // aggiorna snapshot per il prossimo ciclo
            prevTsSnapshot.colore = colDev?.color_sensor_event?.[0]?.ts  ?? prevTsSnapshot.colore;
            prevTsSnapshot.rilev  = rilDev?.infrared_sensor_event?.[0]?.ts ?? prevTsSnapshot.rilev;
            prevTsSnapshot.tempo  = tmpDev?.time?.[0]?.ts                  ?? prevTsSnapshot.tempo;

            if (!firstFetch && anyNew) {
                lastLiveMs = Date.now();
            }
            if (!firstFetch && (Date.now() - lastLiveMs) < LIVE_TIMEOUT_MS) {
                setConnStatus('ok', 'Live');
            } else if (!firstFetch) {
                setConnStatus('waiting', 'Inattivo');
            }
            aggiornaPuntoCorrente();
            const windowMs = currentFormat === 'minuto' ? 60000 : 3600000;
            if (Date.now() - windowStartMs >= windowMs) avanzaFinestraGrafico();
            const minPassati = (Date.now() - rilevWinMs) / 60000;
            if (minPassati >= 1) {
                const rate = Math.round(rilevCount / minPassati);
                document.getElementById('mediaValue').textContent = currentFormat === 'minuto' ? `Media al min: ${rate} p` : `Media oraria: ${Math.round(rate * 60)} p`;
                rilevCount = 0;
                rilevWinMs = Date.now();
            }
        } catch (e) {
            setConnStatus('error', 'Connessione persa');
            console.error('fetchTbData:', e);
        }
    }

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
        const banner = document.getElementById('errorBanner');
        banner.classList.remove('d-none');
        banner.classList.add('d-flex');
        document.getElementById('errorBannerText').textContent = msg;
    }
    function hideErrorBanner() {
        const banner = document.getElementById('errorBanner');
        banner.classList.add('d-none');
        banner.classList.remove('d-flex');
    }

    function toggleTheme() {
    const body = document.body;
    const icon = document.getElementById("themeIcon");

    if (body.getAttribute("data-theme") === "light") {
        body.removeAttribute("data-theme");
        icon.className = "fa-solid fa-sun";
    } else {
        body.setAttribute("data-theme", "light");
        icon.className = "fa-solid fa-moon";
    }
    }

    function toggleColor(id, el) {
        el.classList.toggle('active-filter');
        productionChart.data.datasets[id].hidden = !el.classList.contains('active-filter');
        productionChart.update();
    }

    let pollInterval = setInterval(fetchTbData, POLL_MS);

    function toggleData(btn) {
        const stopBanner = document.getElementById('stopBanner');
        if (!isStopped) {
            clearInterval(pollInterval);
            stopBanner.classList.remove('d-none');
            stopBanner.classList.add('d-flex');
            btn.innerHTML = '<i class="fa-solid fa-play me-2"></i>Riprendi';
            isStopped = true;
        } else {
            pollInterval = setInterval(fetchTbData, POLL_MS);
            stopBanner.classList.add('d-none');
            stopBanner.classList.remove('d-flex');
            btn.innerHTML = '<i class="fa-solid fa-stop me-2"></i>Stop';
            isStopped = false;
            firstFetch = true;  // ri-baseline i timestamp al riavvio
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

    function clearLog() { document.getElementById('logTableBody').innerHTML = ''; }

    initChart();
    fetchTbData();
</script>
</body>
</html>