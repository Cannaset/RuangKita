document.addEventListener("DOMContentLoaded", () => {
    const themeToggle = document.getElementById("themeToggle");
    const themeIcon = document.getElementById("themeIcon");
    const profileMenu = document.querySelector(".profile-menu");
    const profileTrigger = document.getElementById("profileTrigger");
    const toast = document.getElementById("toast");
    const modal = document.getElementById("adminModal");
    const modalBody = document.getElementById("modalBody");
    const modalClose = document.getElementById("modalClose");

    function setTheme(isDark) {
        document.body.classList.toggle("dark-theme", isDark);
        document.documentElement.classList.toggle("dark-theme", isDark);

        if (themeIcon) {
            themeIcon.textContent = isDark ? "Light" : "Dark";
        }
    }

    function applySavedTheme() {
        setTheme(localStorage.getItem("ruangkita-theme") === "dark");
    }

    function showToast(message, isError = false) {
        if (!toast) return;

        toast.textContent = message;
        toast.classList.toggle("error", isError);
        toast.classList.add("show");

        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => {
            toast.classList.remove("show");
        }, 3200);
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
    }

    function renderMedia(imageUrl) {
        if (!imageUrl) return "";

        const safeUrl = escapeHtml(imageUrl);
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
    }

    function renderResponses(responses) {
        if (!responses || responses.length === 0) {
            return `<p class="modal-description">Belum ada tanggapan resmi untuk aspirasi ini.</p>`;
        }

        return `
            <div class="response-list">
                ${responses.map((response) => `
                    <article class="response-item">
                        <strong>${escapeHtml(response.admin_name || "Admin")}</strong>
                        <small>${escapeHtml(formatDate(response.created_at))}</small>
                        <p>${escapeHtml(response.response)}</p>
                    </article>
                `).join("")}
            </div>
        `;
    }

    function openModal(post) {
        if (!modal || !modalBody) return;

        modalBody.innerHTML = `
            <h2 class="modal-title" id="modalTitle">${escapeHtml(post.title)}</h2>
            <div class="modal-meta">
                <span>${escapeHtml(post.author)}</span>
                <span>${escapeHtml(post.category || "Other")}</span>
                <span>${escapeHtml(formatDate(post.createdAt))}</span>
                <span>${Number(post.upvotes || 0)} upvote</span>
                <span>${Number(post.comments || 0)} komentar</span>
                <span class="admin-status-badge ${escapeHtml(post.statusClass)}">${escapeHtml(post.statusLabel)}</span>
            </div>
            <p class="modal-description">${escapeHtml(post.description)}</p>
            ${renderMedia(post.imageUrl)}
            <h3 class="modal-section-title">Informasi Mahasiswa</h3>
            <div class="modal-meta">
                <span>NIM: ${escapeHtml(post.nim)}</span>
                <span>Email: ${escapeHtml(post.email)}</span>
                <span>Update terakhir: ${escapeHtml(formatDate(post.updatedAt))}</span>
            </div>
            <h3 class="modal-section-title">Tanggapan Admin</h3>
            ${renderResponses(post.responses)}
        `;

        modal.hidden = false;
        document.body.style.overflow = "hidden";
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = "";
    }

    async function postForm(form) {
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
    }

    applySavedTheme();

    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            const isDark = !document.body.classList.contains("dark-theme");
            localStorage.setItem("ruangkita-theme", isDark ? "dark" : "light");
            setTheme(isDark);
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

    document.querySelectorAll(".status-form select").forEach((select) => {
        select.addEventListener("change", async () => {
            const form = select.closest(".status-form");
            const postId = form?.dataset.postId;
            const badge = document.querySelector(`[data-status-badge="${postId}"]`);
            const previousValue = select.dataset.previousValue || select.defaultValue;

            select.disabled = true;

            try {
                const data = await postForm(form);

                if (badge) {
                    badge.className = `admin-status-badge ${data.status_class}`;
                    badge.textContent = data.status_label;
                }

                const card = form.closest(".admin-post-card");
                const postData = card?.dataset.post ? JSON.parse(card.dataset.post) : null;

                if (postData) {
                    postData.status = data.status;
                    postData.statusLabel = data.status_label;
                    postData.statusClass = data.status_class;
                    postData.updatedAt = data.updated_at;
                    card.dataset.post = JSON.stringify(postData);
                }

                select.dataset.previousValue = data.status;
                showToast(data.message);
            } catch (error) {
                select.value = previousValue;
                showToast(error.message, true);
            } finally {
                select.disabled = false;
            }
        });
    });

    document.querySelectorAll(".response-form").forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const textarea = form.querySelector("textarea");
            const button = form.querySelector("button");

            if (!textarea || textarea.value.trim() === "") {
                showToast("Tanggapan tidak boleh kosong.", true);
                return;
            }

            button.disabled = true;

            try {
                const data = await postForm(form);
                const card = form.closest(".admin-post-card");
                const postData = card?.dataset.post ? JSON.parse(card.dataset.post) : null;

                if (postData) {
                    postData.responses = postData.responses || [];
                    postData.responses.push(data.response);
                    card.dataset.post = JSON.stringify(postData);
                }

                let latest = card?.querySelector(".latest-response");

                if (!latest && card) {
                    latest = document.createElement("div");
                    latest.className = "latest-response";
                    form.before(latest);
                }

                if (latest) {
                    latest.innerHTML = `
                        <strong>Respons terbaru</strong>
                        <p>${escapeHtml(data.response.response)}</p>
                    `;
                }

                textarea.value = "";
                showToast(data.message);
            } catch (error) {
                showToast(error.message, true);
            } finally {
                button.disabled = false;
            }
        });
    });

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
