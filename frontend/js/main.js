let datiProduzione = [];
let inactivityTimer = null;

window.onload = function () {
    initCharts();
    connectWebSocket();
    checkLogin();
};