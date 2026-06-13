document.addEventListener("DOMContentLoaded", function () {
    // ============================================
    // ELEMENTS
    // ============================================
    const form = document.querySelector("#createPostForm");
    const titleInput = document.querySelector("#title");
    const contentInput = document.querySelector("#content");
    const categorySelect = document.querySelector("#category");
    const fileUploadArea = document.querySelector("#fileUploadArea");
    const fileInput = document.querySelector("#image");
    const filePreview = document.querySelector("#filePreview");
    const previewImg = document.querySelector("#previewImg");
    const titleCount = document.querySelector("#titleCount");
    const contentCount = document.querySelector("#contentCount");
    const themeToggle = document.querySelector("#themeToggle");
    const themeIcon = document.querySelector("#themeIcon");

    // ============================================
    // THEME FUNCTIONS
    // ============================================
    function applySavedTheme() {
        const savedTheme = localStorage.getItem("ruangkita-theme");
        const isDark = savedTheme === "dark";
        document.body.classList.toggle("dark-theme", isDark);
        updateThemeIcon(isDark);
    }

    function updateThemeIcon(isDark) {
        if (themeIcon) {
            themeIcon.textContent = isDark ? "Light" : "Dark";
        }
    }

    function toggleTheme() {
        const isDark = document.body.classList.toggle("dark-theme");
        localStorage.setItem("ruangkita-theme", isDark ? "dark" : "light");
        updateThemeIcon(isDark);
    }

    // ============================================
    // CHARACTER COUNTER
    // ============================================
    if (titleInput && titleCount) {
        titleInput.addEventListener("input", () => {
            titleCount.textContent = titleInput.value.length;
        });
        // Initialize on load
        titleCount.textContent = titleInput.value.length;
    }

    if (contentInput && contentCount) {
        contentInput.addEventListener("input", () => {
            contentCount.textContent = contentInput.value.length;
        });
        // Initialize on load
        contentCount.textContent = contentInput.value.length;
    }

    // ============================================
    // FILE UPLOAD HANDLING
    // ============================================
    if (fileUploadArea && fileInput) {
        // Click to upload
        fileUploadArea.addEventListener("click", () => {
            fileInput.click();
        });

        // File selected from input
        fileInput.addEventListener("change", handleFileSelect);

        // Drag and drop
        fileUploadArea.addEventListener("dragover", (e) => {
            e.preventDefault();
            fileUploadArea.classList.add("drag-over");
        });

        fileUploadArea.addEventListener("dragleave", () => {
            fileUploadArea.classList.remove("drag-over");
        });

        fileUploadArea.addEventListener("drop", (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove("drag-over");

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect();
            }
        });
    }

    function handleFileSelect() {
        const file = fileInput.files[0];

        if (!file) {
            filePreview.style.display = "none";
            return;
        }

        // File size validation (10MB)
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            alert("File terlalu besar. Maksimal 10MB.");
            fileInput.value = "";
            filePreview.style.display = "none";
            return;
        }

        // File type validation
        const validTypes = ["image/jpeg", "image/png", "image/gif", "video/mp4"];
        if (!validTypes.includes(file.type)) {
            alert("Tipe file tidak didukung. Gunakan JPG, PNG, GIF, atau MP4.");
            fileInput.value = "";
            filePreview.style.display = "none";
            return;
        }

        // Preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            filePreview.style.display = "block";
        };
        reader.readAsDataURL(file);
    }

    // ============================================
    // FORM SUBMISSION
    // ============================================
    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const title = titleInput?.value.trim() ?? '';
            const content = contentInput?.value.trim() ?? '';
            const category = categorySelect?.value ?? '';

            if (!title) { alert("Judul tidak boleh kosong"); return; }
            if (!content) { alert("Deskripsi tidak boleh kosong"); return; }
            if (!category) { alert("Pilih kategori"); return; }

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Mengirim...';

            try {
                const fd = new FormData(form);
                const res = await fetch('../api/posts.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    alert('Aspirasi kamu berhasil dikirim! 🎉\n\nAspirasimu sedang menunggu persetujuan admin. Kamu bisa memantau statusnya di halaman Profil → Riwayat Aspirasi.');
                    window.location.href = 'feed.php';
                } else {
                    alert(data.message || 'Gagal mengirim aspirasi.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Kirim Aspirasi';
                }
            } catch {
                alert('Gagal menghubungi server.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kirim Aspirasi';
            }
        });
    }

    // Konfirmasi logout
    document.querySelectorAll('a[href*="logout"]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            if (confirm('Yakin ingin keluar dari RuangKita?')) {
                window.location.href = link.href;
            }
        });
    });

    // ============================================
    // PROFILE DROPDOWN (sama seperti feed)
    // ============================================
    const profileMenu = document.querySelector(".profile-menu");
    const profileTrigger = document.querySelector("#profileTrigger");

    if (profileTrigger && profileMenu) {
        profileTrigger.addEventListener("click", (e) => {
            e.stopPropagation();
            const isOpen = profileMenu.classList.toggle("open");
            profileTrigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        document.addEventListener("click", (e) => {
            if (!profileMenu.contains(e.target)) {
                profileMenu.classList.remove("open");
                profileTrigger.setAttribute("aria-expanded", "false");
            }
        });
    }

    // ============================================
    // THEME TOGGLE
    // ============================================
    if (themeToggle) {
        themeToggle.addEventListener("click", toggleTheme);
    }

    // ============================================
    // INITIALIZE ON PAGE LOAD
    // ============================================
    applySavedTheme();
});