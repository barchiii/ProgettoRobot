<?php
session_start();

// Controllo: se non sei loggato, vieni rispedito al login
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
        /* FIX LEGGIBILITÀ NAVBAR - TESTO BENVENUTO */
        .navbar-custom .navbar-text {
            color: #333 !important; /* Testo scuro per White Mode */
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        /* Regola specifica per Dark Mode: il testo diventa bianco */
        [data-theme="dark"] .navbar-custom .navbar-text,
        body:not([data-theme="light"]) .navbar-custom .navbar-text {
            color: #ffffff !important;
        }

        /* Container per l'usura */
        .usura-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

       /* Barra verticale con bordo chiaro di default */
        .progress-vertical {
            width: 35px;
            height: 160px;
            background: rgba(255, 255, 255, 0.1); /* Sfondo semi-trasparente chiaro */
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.4); /* Bordo chiaro tipo Dark Mode */
            transition: all 0.3s ease;
        }

        /* Opzionale: Se vuoi che torni scuro solo quando attivi esplicitamente la White Mode */
        [data-theme="light"] .progress-vertical {
            border: 2px solid rgba(0, 0, 0, 0.3);
            background: rgba(0, 0, 0, 0.05);
        }

        .progress-bar-fill {
            position: absolute;
            bottom: 0;
            width: 100%;
            background: linear-gradient(to top, #00fa9a, #ffd700, #ff4d4d);
            transition: height 0.5s ease;
        }

        /* Animazione per l'alert critico sopra l'85% */
        @keyframes pulse-critical {
            0% { box-shadow: 0 0 0 0 rgba(255, 77, 77, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(255, 77, 77, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 77, 77, 0); }
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
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom py-3">
    <div class="container-fluid">
            <span class="navbar-text ms-2 fw-bold">
                Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
        <div class="ms-auto">
            <a class="nav-link" href="logout.php"><i class="fa-solid fa-right-from-bracket me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<div class="dashboard-container">

    <div id="usuraAlert" class="alert alert-danger alert-critical-banner align-items-center mb-3" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-3 fa-lg"></i>
        <div>LIVELLO USURA CRITICO: Il sistema richiede manutenzione immediata (>85%).</div>
    </div>

    <div id="stopBanner" class="alert alert-warning alert-custom d-flex align-items-center" role="alert" style="display:none;">
        <i class="fa-solid fa-circle-exclamation me-3"></i>
        <div><strong>Sistema In Pausa:</strong> La ricezione dei dati è sospesa.</div>
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
                    <li class="active-filter" onclick="toggleColor(3, this)"><span class="color-dot dot-rosa"></span>Rosa</li>
                    <li class="active-filter" onclick="toggleColor(0, this)"><span class="color-dot dot-blu"></span>Blu</li>
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <div class="custom-card" style="position: relative; height: 250px;">
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
                <button class="btn btn-custom" onclick="clearLog()"><i class="fa-solid fa-trash me-2"></i>Cancella log</button>
            </div>
            <button class="btn btn-custom rounded-circle p-2 px-3" onclick="toggleTheme()">
                <i id="themeIcon" class="fa-solid fa-sun"></i>
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="custom-card" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-transparent">
                    <thead>
                    <tr>
                        <th>Colore prodotto</th>
                        <th>Data</th>
                        <th>Ora</th>
                    </tr>
                    </thead>
                    <tbody id="logTableBody">
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-3">
            <div class="custom-card d-flex align-items-center justify-content-center">
                <h4 class="mb-0 fw-bold" id="mediaValue">Media al min: Calcolando...</h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="custom-card usura-container">
                <h6 class="fw-bold mb-2">Usura: <span id="usuraPercentText">10.0%</span></h6>
                <div id="usuraBorder" class="progress-vertical">
                    <div id="usuraFill" class="progress-bar-fill" style="height: 10%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('productionChart').getContext('2d');
    let labelTime = 0;
    let currentFormat = 'minuto';
    let currentUsura = 10.0;

    const productionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'Blu', data: [], borderColor: '#1e90ff', tension: 0.3, borderWidth: 2 },
                { label: 'Giallo', data: [], borderColor: '#ffd700', tension: 0.3, borderWidth: 2 },
                { label: 'Verde', data: [], borderColor: '#00fa9a', tension: 0.3, borderWidth: 2 },
                { label: 'Rosa', data: [], borderColor: '#ff69b4', tension: 0.3, borderWidth: 2 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(128, 128, 128, 0.1)' }, border: { display: false } },
                x: { grid: { display: false }, border: { display: false } }
            }
        }
    });

    function generaDati() {
        const colori = [
            { id: 0, nome: 'Blu', classe: 'dot-blu' },
            { id: 1, nome: 'Giallo', classe: 'dot-giallo' },
            { id: 2, nome: 'Verde', classe: 'dot-verde' },
            { id: 3, nome: 'Rosa', classe: 'dot-rosa' }
        ];
        const coloreScelto = colori[Math.floor(Math.random() * colori.length)];
        const adesso = new Date();

        const dataOggi = adesso.toLocaleDateString('it-IT');
        const oraAdesso = adesso.toLocaleTimeString('it-IT');

        const tbody = document.getElementById('logTableBody');
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><span class="color-dot ${coloreScelto.classe}"></span>${coloreScelto.nome}</td>
                            <td>${dataOggi}</td>
                            <td>${oraAdesso}</td>`;
        tbody.insertBefore(tr, tbody.firstChild);
        if (tbody.children.length > 8) tbody.removeChild(tbody.lastChild);

        let sommaRound = 0;
        productionChart.data.datasets.forEach(dataset => {
            let val = Math.floor(Math.random() * 90) + 10;
            if (currentFormat === 'ora') val *= 10;
            dataset.data.push(val);
            sommaRound += val;
        });

        // Usura aumenta in base alla velocità
        let incremento = (sommaRound / (currentFormat === 'minuto' ? 600 : 6000));
        currentUsura = Math.min(100, currentUsura + incremento);
        updateUsuraDisplay(currentUsura);

        // AGGIORNAMENTO MEDIA (RIPRISTINATO)
        const textMedia = currentFormat === 'minuto' ? 'Media al min:' : 'Media oraria:';
        const valMedia = currentFormat === 'minuto' ? Math.floor(Math.random() * 5 + 2) : Math.floor(Math.random() * 500 + 200);
        document.getElementById('mediaValue').innerText = `${textMedia} ${valMedia}p`;

        labelTime++;
        productionChart.data.labels.push(`+${labelTime}${currentFormat[0]}`);
        if (productionChart.data.labels.length > 10) {
            productionChart.data.labels.shift();
            productionChart.data.datasets.forEach(d => d.data.shift());
        }
        productionChart.update();
    }

    function updateUsuraDisplay(valore) {
        const perc = valore.toFixed(1);
        document.getElementById('usuraPercentText').innerText = perc + '%';
        document.getElementById('usuraFill').style.height = perc + '%';
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

    let isStopped = false;
    let dataInterval = setInterval(generaDati, 2500);

    function toggleData(btn) {
        if (!isStopped) {
            clearInterval(dataInterval);
            document.getElementById('stopBanner').style.display = 'flex';
            btn.innerHTML = '<i class="fa-solid fa-play me-2"></i>Riprendi';
            isStopped = true;
        } else {
            dataInterval = setInterval(generaDati, 2500);
            document.getElementById('stopBanner').style.display = 'none';
            btn.innerHTML = '<i class="fa-solid fa-stop me-2"></i>Stop';
            isStopped = false;
        }
    }

    function changeTimeFormat(f, el) {
        document.querySelectorAll('#timeFilters li').forEach(li => li.classList.remove('active-filter'));
        el.classList.add('active-filter');
        currentFormat = f;
        labelTime = 0;
        productionChart.data.labels = [];
        productionChart.data.datasets.forEach(d => d.data = []);
        productionChart.update();
    }

    function clearLog() { document.getElementById('logTableBody').innerHTML = ''; }
</script>
</body>
</html>