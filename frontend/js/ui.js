function updateStatus(text, status) {

    const el = document.getElementById("connectionStatus");

    el.textContent = text;
    el.className = "badge px-3 py-2 status-" + status;
}

function showToast(message, type) {

    const container = document.querySelector(".toast-container");

    const toast = document.createElement("div");

    toast.className = `toast text-white bg-${type}`;

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button class="btn-close btn-close-white m-auto me-2"
                data-bs-dismiss="toast"></button>
        </div>
    `;

    container.appendChild(toast);

    new bootstrap.Toast(toast).show();

    setTimeout(() => toast.remove(), 5000);
}