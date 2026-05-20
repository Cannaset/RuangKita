// ============================================================
// RuangKita - JS Filters, Formats & Helpers
// File: App/admin/assets/js/admin-filter.js
// ============================================================

window.showToast = function(message, isError = false) {
    const toast = document.getElementById("toast");
    if (!toast) return;

    toast.textContent = message;
    toast.classList.toggle("error", isError);
    toast.classList.add("show");

    window.clearTimeout(window.showToast.timeout);
    window.showToast.timeout = window.setTimeout(() => {
        toast.classList.remove("show");
    }, 3200);
};

window.escapeHtml = function(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
};

window.formatDate = function(value) {
    if (!value) return "-";
    const date = new Date(String(value).replace(" ", "T"));

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

window.renderMedia = function(imageUrl) {
    if (!imageUrl) return "";

    const safeUrl = window.escapeHtml(imageUrl);
    const lowerUrl = imageUrl.toLowerCase();

    if (lowerUrl.endsWith(".mp4") || lowerUrl.endsWith(".webm") || lowerUrl.endsWith(".ogg")) {
        return `
            <div class="modal-media">
                <video controls src="${safeUrl}"></video>
            </div>
        `;
    }

    return `
        <div class="modal-media">
            <img src="${safeUrl}" alt="Lampiran aspirasi">
        </div>
    `;
};

window.renderResponses = function(responses) {
    if (!responses || responses.length === 0) {
        return `<p class="modal-description">Belum ada tanggapan resmi untuk aspirasi ini.</p>`;
    }

    return `
        <div class="response-list">
            ${responses.map((response) => `
                <article class="response-item">
                    <strong>${window.escapeHtml(response.admin_name || "Admin")}</strong>
                    <small>${window.escapeHtml(window.formatDate(response.created_at))}</small>
                    <p>${window.escapeHtml(response.response)}</p>
                </article>
            `).join("")}
        </div>
    `;
};

window.postForm = async function(form) {
    const response = await fetch(window.location.href, {
        method: "POST",
        body: new FormData(form),
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || "Terjadi kesalahan.");
    }

    return data;
};
