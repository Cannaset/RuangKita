document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // ELEMENTS
    // ============================================
    const form          = document.querySelector('#createPostForm');
    const titleInput    = document.querySelector('#title');
    const contentInput  = document.querySelector('#content');
    const categorySelect = document.querySelector('#category');
    const fileUploadArea = document.querySelector('#fileUploadArea');
    const fileInput     = document.querySelector('#image');
    const filePreview   = document.querySelector('#filePreview');
    const previewImg    = document.querySelector('#previewImg');
    const titleCount    = document.querySelector('#titleCount');
    const contentCount  = document.querySelector('#contentCount');
    const themeToggle   = document.querySelector('#themeToggle');
    const themeIcon     = document.querySelector('#themeIcon');
    const submitBtn     = document.querySelector('#submitBtn');
    const formMessage   = document.querySelector('#formMessage');

    // ============================================
    // THEME
    // ============================================
    function applySavedTheme() {
        const isDark = localStorage.getItem('ruangkita-theme') === 'dark';
        document.body.classList.toggle('dark-theme', isDark);
        if (themeIcon) themeIcon.textContent = isDark ? 'Light' : 'Dark';
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('dark-theme');
            localStorage.setItem('ruangkita-theme', isDark ? 'dark' : 'light');
            if (themeIcon) themeIcon.textContent = isDark ? 'Light' : 'Dark';
        });
    }

    // ============================================
    // CHARACTER COUNTER
    // ============================================
    if (titleInput && titleCount) {
        titleInput.addEventListener('input', () => {
            titleCount.textContent = titleInput.value.length;
        });
    }

    if (contentInput && contentCount) {
        contentInput.addEventListener('input', () => {
            contentCount.textContent = contentInput.value.length;
        });
    }

    // ============================================
    // FILE UPLOAD
    // ============================================
    if (fileUploadArea && fileInput) {
        fileUploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', handleFileSelect);

        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('drag-over');
        });
        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('drag-over');
        });
        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            }
        });
    }

    function handleFileSelect() {
        const file = fileInput.files[0];
        if (!file) { filePreview.style.display = 'none'; return; }

        if (file.size > 10 * 1024 * 1024) {
            showMessage('File terlalu besar. Maksimal 10MB.', 'error');
            fileInput.value = '';
            filePreview.style.display = 'none';
            return;
        }

        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
        if (!validTypes.includes(file.type)) {
            showMessage('Tipe file tidak didukung. Gunakan JPG, PNG, GIF, atau MP4.', 'error');
            fileInput.value = '';
            filePreview.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            filePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // ============================================
    // SHOW MESSAGE HELPER
    // ============================================
    function showMessage(text, type) {
        if (!formMessage) return;
        formMessage.textContent = text;
        formMessage.style.display = 'block';
        formMessage.style.background = type === 'error' ? '#fee2e2' : '#dcfce7';
        formMessage.style.color      = type === 'error' ? '#991b1b' : '#166534';
        formMessage.style.border     = type === 'error' ? '1px solid #fca5a5' : '1px solid #86efac';
    }

    // ============================================
    // FORM SUBMISSION — kirim ke posts.php
    // ============================================
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const title    = titleInput?.value.trim()    ?? '';
            const content  = contentInput?.value.trim()  ?? '';
            const category = categorySelect?.value        ?? '';

            if (!title)    { showMessage('Judul tidak boleh kosong.', 'error');    return; }
            if (!content)  { showMessage('Deskripsi tidak boleh kosong.', 'error'); return; }
            if (!category) { showMessage('Pilih kategori terlebih dahulu.', 'error'); return; }

            // Disable tombol supaya tidak double-submit
            submitBtn.disabled    = true;
            submitBtn.textContent = 'Mengirim...';

            try {
                const formData = new FormData(form);

                const res  = await fetch('../api/posts.php', {
                    method: 'POST',
                    body: formData,
                });
                const data = await res.json();

                if (data.success) {
                    showMessage('✓ Aspirasi berhasil dikirim! Mengalihkan ke feed...', 'success');
                    setTimeout(() => {
                        window.location.href = 'feed.php';
                    }, 1500);
                } else {
                    showMessage('✕ ' + (data.message ?? 'Gagal mengirim aspirasi.'), 'error');
                    submitBtn.disabled    = false;
                    submitBtn.textContent = 'Kirim Aspirasi';
                }

            } catch (err) {
                console.error('Submit error:', err);
                showMessage('✕ Terjadi kesalahan. Coba lagi.', 'error');
                submitBtn.disabled    = false;
                submitBtn.textContent = 'Kirim Aspirasi';
            }
        });
    }

    // ============================================
    // PROFILE DROPDOWN
    // ============================================
    const profileMenu    = document.querySelector('.profile-menu');
    const profileTrigger = document.querySelector('#profileTrigger');

    if (profileTrigger && profileMenu) {
        profileTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = profileMenu.classList.toggle('open');
            profileTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        document.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target)) {
                profileMenu.classList.remove('open');
                profileTrigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ============================================
    // INIT
    // ============================================
    applySavedTheme();
});
