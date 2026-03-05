let ws;

function connectWebSocket() {

    ws = new WebSocket("ws://localhost:8080/ws");

    ws.onopen = function () {
        updateStatus("ONLINE", "online");
        showToast("Connesso al server", "success");
    };

    ws.onmessage = function (event) {

        const data = JSON.parse(event.data);
        processData(data);

        resetTimer();
    };

    ws.onclose = function () {

        updateStatus("OFFLINE", "offline");
        showToast("Connessione persa", "danger");

        setTimeout(connectWebSocket, 3000);
    };
}