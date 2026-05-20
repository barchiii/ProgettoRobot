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
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom py-3">
    <div class="container-fluid">
        <!-- Scritta di benvenuto con l'username dell'account presente nella navbar -->
        <span class="navbar-text ms-2 fw-bold">
            Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>
            <span id="connStatus" class="conn-waiting">
                <!-- Stato della connessione -->
                 <i class="fa-solid fa-circle-notch fa-spin me-1"></i>In attesa…
                <i class="fa-solid fa-circle-notch fa-spin me-1"></i>Connessione…
            </span>
        </span>
        <div class="ms-auto">
            <a class="nav-link" href="logout.php">
                <!-- Pulsante di logout -->
                <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="dashboard-container">

    <!-- Pop up di usura critica quando supera l'85% -->
    <div id="usuraAlert"
         class="alert alert-danger alert-critical-banner d-none align-items-center mb-3"
         role="alert">
        <i class="fa-solid fa-triangle-exclamation me-3 fa-lg"></i>
        <div>LIVELLO USURA CRITICO: Il sistema richiede manutenzione immediata (&gt;85%).</div>
    </div>

    <!-- Pop up del sistema in pausa -->
    <div id="stopBanner"
         class="alert alert-warning alert-custom d-none align-items-center"
         role="alert">
        <i class="fa-solid fa-circle-exclamation me-3"></i>
        <div><strong>Sistema In Pausa:</strong> La ricezione dei dati è sospesa.</div>
    </div>

    <!-- Pop up di errore di comunicazione con ThingsBoard -->
    <div id="errorBanner"
         class="alert alert-danger alert-custom d-none align-items-center"
         role="alert">
        <i class="fa-solid fa-triangle-exclamation me-3"></i>
        <div id="errorBannerText">Errore di comunicazione con ThingsBoard.</div>
    </div>

    <div class="row g-4 mb-3">

    <!-- Filtri di visualizzazione del grafico per ora o per minuto -->
        <div class="col-md-2">
            <div class="custom-card">
                <ul class="filter-list" id="timeFilters">
                    <li class="active-filter" onclick="changeTimeFormat('minuto', this)">Per minuto</li>
                    <li onclick="changeTimeFormat('ora', this)">Per ora</li>
                </ul>
            </div>
        </div>

        <!-- Filtri di visualizzazione del grafico per colore -->
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
                <!-- Pulsante per fermare o riprendere la ricezione dei dati da thingsboard -->
                <button id="toggleBtn" class="btn btn-custom me-2" onclick="toggleData(this)">
                    <i class="fa-solid fa-stop me-2"></i>Stop
                </button>
                <!-- Pulsante per resettare i dati visualizzati nel log -->
                <button class="btn btn-custom" onclick="clearLog()">
                    <i class="fa-solid fa-trash me-2"></i>Cancella log
                </button>
            </div>
            <!-- Pulsante per cambiare il tema da scuro a chiaro -->
            <button class="btn btn-custom rounded-circle p-2 px-3" onclick="toggleTheme()">
                <i id="themeIcon" class="fa-solid fa-sun"></i>
            </button>
        </div>
    </div>

    <div class="row g-4">

        <!-- Tabella di log con i dati dei prodotti, colore prodotto,data e ora -->
        <div class="col-md-7">
            <div class="custom-card" style="max-height:300px; overflow-y:auto;">
                <table class="table table-transparent">
                    <thead>
                    <tr>
                        <th>Colore prodotto</th>
                        <th>Data</th>
                        <th>Ora</th>
                    </tr>
                    </thead>
                    <tbody id="logTableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Box con la media dei prodotti al minuto o all'ora -->
        <div class="col-md-3">
            <div class="custom-card d-flex flex-column align-items-center justify-content-center gap-3">
                <h4 class="mb-0 fw-bold text-center" id="mediaValue">In attesa dati…</h4>
                <div class="text-center" style="opacity:0.75; font-size:0.9rem; line-height:1.7;">
                </div>
            </div>
        </div>

        <!-- Livello di usura attuale -->
        <div class="col-md-2">
            <div class="custom-card usura-container">
                <h6 class="fw-bold mb-2">Usura: <span id="usuraPercentText">0.0%</span></h6>
                <div id="usuraBorder" class="progress-vertical">
                    <div id="usuraFill" class="progress-bar-fill" style="height:0%;">
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    
    //tempo ideale in ms tra un prodotto e il successivo, se il tempo svolto supera i 3 secondi l'usura inizia ad aumentare
    const TEMPO_IDEALE_MS   = 3000;
    
    //tempo massimo tollerato tra un prodotto e il successivo, se il tempo svolto supera i 120 secondi il livello di usura raggiunge il 100%
    const TEMPO_ESAURITO_MS = 120000;

    //Limite massimo di incremento dell'usura in un singolo ciclo, per evitare che ci siano degli aumenti di usura anomali
    const USURA_STEP_MAX = 3.0;

    //Intervallo di tempo in ms per la richiesta di nuovi dati a ThingsBoard
    const POLL_MS = 3000;

    //Tempo in ms dopo il quale, se non arrivano nuovi dati, si considera la connessione "inattiva"
    const LIVE_TIMEOUT_MS = 12000; 

    //valore dell'usura
    let currentUsura  = 0.0;
    let currentFormat = 'minuto';
    //indica se il sistema è in pausa oppure no, se è true la ricezione dei dati è sospesa
    //se è false la ricezione dei dati è attiva
    let isStopped     = false;

    //Telemetrie 
    let prevColorTs = 0;
    let lastColorTs = 0;
    let lastRilevTs = 0;
    let lastTempoTs = 0;
    let firstFetch  = true; // al primo fetch memorizziamo i ts senza processare eventi
    let prevTsSnapshot = { colore: 0, rilev: 0, tempo: 0 }; // snapshot ts del ciclo precedente
    
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