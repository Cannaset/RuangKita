// ============================================================
// RuangKita - Admin Status & Response Actions
// File: App/assets/admin/js/admin-status.js
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".status-form select").forEach((select) => {
        select.dataset.previousValue = select.value;

        select.addEventListener("change", async () => {
            const form = select.closest(".status-form");
            const postId = form?.dataset.postId;
            const badge = document.querySelector(`[data-status-badge="${postId}"]`);
            const previousValue = select.dataset.previousValue || select.defaultValue;

            if (!form) return;

            select.disabled = true;

            try {
                const formData = new FormData();
                formData.set("action", "update_status");
                formData.set("post_id", form.querySelector('[name="post_id"]')?.value || postId || "");
                formData.set("status", select.value);

                const data = await window.postForm(formData);

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
                window.showToast(data.message);
            } catch (error) {
                select.value = previousValue;
                window.showToast(error.message, true);
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
                window.showToast("Tanggapan tidak boleh kosong.", true);
                return;
            }

            button.disabled = true;

            try {
                const data = await window.postForm(form);
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
                        <p>${window.escapeHtml(data.response.response)}</p>
                    `;
                }

                textarea.value = "";
                window.showToast(data.message);
            } catch (error) {
                window.showToast(error.message, true);
            } finally {
                button.disabled = false;
            }
        });
    });
    document.querySelectorAll(".delete-post-button").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const postId = btn.dataset.postId;
            if (!confirm("Yakin ingin menghapus aspirasi ini? Tindakan ini tidak bisa dibatalkan.")) return;

            btn.disabled = true;
            btn.textContent = "Menghapus...";

            try {
                const res = await fetch(window.location.pathname, {
                    method: "POST",
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                    body: (() => {
                        const fd = new FormData();
                        fd.set("action", "delete_post");
                        fd.set("post_id", postId);
                        return fd;
                    })(),
                });
                const data = await res.json();

                if (data.success) {
                    const card = btn.closest(".admin-post-card");
                    if (card) {
                        card.style.transition = "opacity .3s, transform .3s";
                        card.style.opacity = "0";
                        card.style.transform = "translateY(-8px)";
                        setTimeout(() => card.remove(), 300);
                    }
                    window.showToast("Aspirasi berhasil dihapus.");
                } else {
                    window.showToast(data.message || "Gagal menghapus.", true);
                    btn.disabled = false;
                    btn.textContent = "Hapus";
                }
            } catch (err) {
                window.showToast("Gagal menghubungi server.", true);
                btn.disabled = false;
                btn.textContent = "Hapus";
            }
        });
    });
});
