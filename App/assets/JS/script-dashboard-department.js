document.addEventListener("DOMContentLoaded", () => {
    const postDataElement = document.getElementById("departmentPostData");
    const modal = document.getElementById("departmentPostModal");
    const modalBody = document.getElementById("departmentModalBody");
    const closeButton = document.getElementById("departmentModalClose");
    const themeToggle = document.getElementById("themeToggle");
    const themeIcon = document.getElementById("themeIcon");
    const profileMenu = document.querySelector(".profile-menu");
    const profileTrigger = document.getElementById("profileTrigger");
    const toast = document.getElementById("deptToast");

    document.querySelectorAll("[data-auto-submit]").forEach((field) => {
        field.addEventListener("change", () => {
            const form = field.closest("form");
            if (!form) return;

            const pageField = form.querySelector('[name="page"]');
            if (pageField) pageField.remove();
            form.submit();
        });
    });

    let departmentPosts = {};

    if (postDataElement) {
        try {
            departmentPosts = JSON.parse(postDataElement.textContent);
        } catch {
            departmentPosts = {};
        }
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function formatDate(value) {
        if (!value) return "-";

        const date = new Date(String(value).replace(" ", "T"));
        if (Number.isNaN(date.getTime())) return escapeHtml(value);

        return escapeHtml(date.toLocaleString("id-ID", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        }));
    }

    function renderDetailMedia(url) {
        if (!url) return "";

        const safeUrl = escapeHtml(url);
        const path = String(url).split(/[?#]/)[0].toLowerCase();
        const isVideo = [".mp4", ".webm", ".ogg"].some((extension) => path.endsWith(extension));

        return `<div class="modal-media">${isVideo
            ? `<video controls preload="metadata" src="${safeUrl}"></video>`
            : `<img src="${safeUrl}" alt="Lampiran aspirasi">`
        }</div>`;
    }

    function renderAdminResponses(responses) {
        if (!Array.isArray(responses) || responses.length === 0) {
            return '<p class="modal-description">Belum ada tanggapan resmi untuk aspirasi ini.</p>';
        }

        return `<div class="response-list">${responses.map((response) => `
            <article class="response-item">
                <strong>${escapeHtml(response.admin_name || "Admin")}</strong>
                <small>${formatDate(response.created_at)}</small>
                <p>${escapeHtml(response.response)}</p>
            </article>
        `).join("")}</div>`;
    }

    function showToast(message, type = "success") {
        if (!toast) return;

        toast.textContent = message;
        toast.style.background = type === "success" ? "#22c55e" : "#ef4444";
        toast.style.display = "block";

        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => {
            toast.style.display = "none";
        }, 3000);
    }

    function closeModal() {
        if (!modal) return;

        modal.hidden = true;
        document.body.style.overflow = "";
    }

    document.querySelectorAll(".detail-post-btn").forEach((button) => {
        button.addEventListener("click", () => {
            const post = departmentPosts[button.dataset.postId];

            if (!post) {
                showToast("Detail postingan tidak ditemukan.", "error");
                return;
            }

            if (!modal || !modalBody) return;

            modalBody.innerHTML = `
                <h2 class="modal-title" id="departmentModalTitle">${escapeHtml(post.title)}</h2>
                <div class="modal-meta">
                    <span>${escapeHtml(post.author)}</span>
                    <span>${escapeHtml(post.category)}</span>
                    <span>${formatDate(post.createdAt)}</span>
                    <span>${Number(post.upvotes || 0)} upvote</span>
                    <span>${Number(post.downvotes || 0)} downvote</span>
                    <span>${Number(post.comments || 0)} komentar</span>
                    <span class="admin-status-badge" style="background:${escapeHtml(post.statusColor)};color:#fff;">
                        ${escapeHtml(post.statusLabel)}
                    </span>
                </div>
                <p class="modal-description">${escapeHtml(post.description)}</p>
                ${renderDetailMedia(post.imageUrl)}
                <h3 class="modal-section-title">Informasi Mahasiswa</h3>
                <div class="modal-meta">
                    <span>NIM: ${escapeHtml(post.nim)}</span>
                    <span>Email: ${escapeHtml(post.email)}</span>
                    <span>Update terakhir: ${formatDate(post.updatedAt)}</span>
                </div>
                <h3 class="modal-section-title">Tanggapan Admin</h3>
                ${renderAdminResponses(post.responses)}
            `;

            modal.hidden = false;
            document.body.style.overflow = "hidden";
        });
    });

    const isDark = localStorage.getItem("ruangkita-theme") === "dark";
    document.body.classList.toggle("dark-theme", isDark);

    if (themeIcon) {
        themeIcon.textContent = isDark ? "Light" : "Dark";
    }

    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            const dark = document.body.classList.toggle("dark-theme");
            localStorage.setItem("ruangkita-theme", dark ? "dark" : "light");

            if (themeIcon) {
                themeIcon.textContent = dark ? "Light" : "Dark";
            }
        });
    }

    if (profileMenu && profileTrigger) {
        profileTrigger.addEventListener("click", (event) => {
            event.stopPropagation();
            const isOpen = profileMenu.classList.toggle("open");
            profileTrigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        document.addEventListener("click", (event) => {
            if (!profileMenu.contains(event.target)) {
                profileMenu.classList.remove("open");
                profileTrigger.setAttribute("aria-expanded", "false");
            }
        });
    }

    document.querySelectorAll(".save-status-btn").forEach((button) => {
        button.addEventListener("click", async () => {
            const card = button.closest(".aspiration-card");
            const status = card?.querySelector(".status-select")?.value ?? "";
            const note = card?.querySelector(".note-input")?.value ?? "";

            if (!status) {
                showToast("Pilih status terlebih dahulu.", "error");
                return;
            }

            button.disabled = true;
            button.textContent = "Menyimpan...";

            const formData = new FormData();
            formData.append("action", "update_status");
            formData.append("post_id", button.dataset.postId);
            formData.append("status", status);
            formData.append("note", note);

            try {
                const response = await fetch("dashboard.php", {
                    method: "POST",
                    body: formData,
                });
                const data = await response.json();

                showToast(data.message, data.success ? "success" : "error");
                if (data.success) {
                    window.setTimeout(() => window.location.reload(), 1000);
                }
            } catch {
                showToast("Gagal menghubungi server.", "error");
            } finally {
                button.disabled = false;
                button.textContent = "Simpan";
            }
        });
    });

    if (closeButton) {
        closeButton.addEventListener("click", closeModal);
    }

    if (modal) {
        modal.addEventListener("click", (event) => {
            if (event.target === modal) closeModal();
        });
    }

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal && !modal.hidden) {
            closeModal();
        }
    });
});
