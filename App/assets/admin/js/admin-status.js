document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".status-form select").forEach((select) => {
        select.addEventListener("change", async () => {
            const form = select.closest(".status-form");
            const postId = form?.dataset.postId;
            const badge = document.querySelector(`[data-status-badge="${postId}"]`);
            const previousValue = select.dataset.previousValue || select.defaultValue;

            select.disabled = true;

            try {
                const data = await window.postForm(form);

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
});
