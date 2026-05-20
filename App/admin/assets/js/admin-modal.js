document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("adminModal");
    const modalBody = document.getElementById("modalBody");
    const modalClose = document.getElementById("modalClose");

    function openModal(post) {
        if (!modal || !modalBody) return;

        modalBody.innerHTML = `
            <h2 class="modal-title" id="modalTitle">${window.escapeHtml(post.title)}</h2>
            <div class="modal-meta">
                <span>${window.escapeHtml(post.author)}</span>
                <span>${window.escapeHtml(post.category || "Other")}</span>
                <span>${window.escapeHtml(window.formatDate(post.createdAt))}</span>
                <span>${Number(post.upvotes || 0)} upvote</span>
                <span>${Number(post.comments || 0)} komentar</span>
                <span class="admin-status-badge ${window.escapeHtml(post.statusClass)}">${window.escapeHtml(post.statusLabel)}</span>
            </div>
            <p class="modal-description">${window.escapeHtml(post.description)}</p>
            ${window.renderMedia(post.imageUrl)}
            <h3 class="modal-section-title">Informasi Mahasiswa</h3>
            <div class="modal-meta">
                <span>NIM: ${window.escapeHtml(post.nim)}</span>
                <span>Email: ${window.escapeHtml(post.email)}</span>
                <span>Update terakhir: ${window.escapeHtml(window.formatDate(post.updatedAt))}</span>
            </div>
            <h3 class="modal-section-title">Tanggapan Admin</h3>
            ${window.renderResponses(post.responses)}
        `;

        modal.hidden = false;
        document.body.style.overflow = "hidden";
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = "";
    }

    document.querySelectorAll(".admin-post-card").forEach((card) => {
        card.addEventListener("click", (event) => {
            if (event.target.closest("form") || event.target.closest("select") || event.target.closest("textarea")) {
                return;
            }

            if (!event.target.closest(".card-open") && !event.target.closest(".detail-button")) {
                return;
            }

            const post = JSON.parse(card.dataset.post || "{}");
            openModal(post);
        });
    });

    if (modalClose) {
        modalClose.addEventListener("click", closeModal);
    }

    if (modal) {
        modal.addEventListener("click", (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal && !modal.hidden) {
            closeModal();
        }
    });
});
