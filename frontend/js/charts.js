let chartOrario;
let chartMinutario;

function initCharts() {

    chartOrario = new Chart(
        document.getElementById("chartOrario"),
        {
            type: "line",
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        }
    );

    chartMinutario = new Chart(
        document.getElementById("chartMinutario"),
        {
            type: "line",
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        }
    );

}