// ============================================================
// script-profile.js – RuangKita Profile Page
// ============================================================

// ── THEME ────────────────────────────────────────────────
function applySavedTheme() {
    const isDark = localStorage.getItem('ruangkita-theme') === 'dark';
    document.body.classList.toggle('dark-theme', isDark);
    const icon = document.getElementById('themeIcon');
    if (icon) icon.textContent = isDark ? 'Light' : 'Dark';
}

function initThemeToggle() {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    btn.addEventListener('click', () => {
        const isDark = document.body.classList.toggle('dark-theme');
        localStorage.setItem('ruangkita-theme', isDark ? 'dark' : 'light');
        const icon = document.getElementById('themeIcon');
        if (icon) icon.textContent = isDark ? 'Light' : 'Dark';
    });
}

// ── PROFILE DROPDOWN ─────────────────────────────────────
function initProfileDropdown() {
    const menu = document.querySelector('.profile-menu');
    const trigger = document.getElementById('profileTrigger');
    if (!menu || !trigger) return;

    trigger.addEventListener('click', e => {
        e.stopPropagation();
        const open = menu.classList.toggle('open');
        trigger.setAttribute('aria-expanded', open);
    });

    document.addEventListener('click', e => {
        if (!menu.contains(e.target)) {
            menu.classList.remove('open');
            trigger.setAttribute('aria-expanded', false);
        }
    });
}

// ── TABS ─────────────────────────────────────────────────
function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanels.forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const panel = document.getElementById('tab-' + target);
            if (panel) panel.classList.add('active');
        });
    });
}

// ── PASSWORD VISIBILITY TOGGLE ───────────────────────────
function initPasswordToggles() {
    document.querySelectorAll('.pw-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) return;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.textContent = isHidden ? '🙈' : '👁';
        });
    });
}

// ── EDIT PROFILE FORM ────────────────────────────────────
function initEditProfileForm() {
    const form = document.getElementById('editProfileForm');
    const msg = document.getElementById('editMsg');
    if (!form || !msg) return;

    form.addEventListener('submit', async e => {
        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
        msg.style.display = 'none';

        try {
            const fd = new FormData(form);

            fd.set('user_type', window.USER_TYPE);
            fd.set('user_id', window.USER_ID);

            const res = await fetch('../api/update-profile.php', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();

            showMsg(msg, data.success ? 'success' : 'error',
                data.message || (data.success ? 'Profil berhasil diperbarui!' : 'Terjadi kesalahan.'));

            if (data.success) {
                // Update display name
                const displayName = document.getElementById('displayName');
                const newUsername = form.querySelector('[name="username"]')?.value;
                if (displayName && newUsername) displayName.textContent = newUsername;
            }
        } catch {
            showMsg(msg, 'error', 'Gagal menghubungi server. Coba lagi.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Simpan Perubahan';
        }
    });
}

// ── CHANGE PASSWORD FORM ─────────────────────────────────
function initChangePasswordForm() {
    const form = document.getElementById('changePasswordForm');
    const msg = document.getElementById('pwMsg');
    if (!form || !msg) return;

    form.addEventListener('submit', async e => {
        e.preventDefault();

        const newPw = form.querySelector('[name="new_password"]')?.value ?? '';
        const confirmPw = form.querySelector('[name="confirm_password"]')?.value ?? '';

        if (newPw.length < 6) {
            return showMsg(msg, 'error', 'Password baru minimal 8 karakter.');
        }
        if (newPw !== confirmPw) {
            return showMsg(msg, 'error', 'Konfirmasi password tidak cocok.');
        }

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
        msg.style.display = 'none';

        try {
            const formData = new FormData(form);

            formData.set("action", "change_password");

            const res = await fetch('../api/update-profile.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            showMsg(msg, data.success ? 'success' : 'error',
                data.message || (data.success ? 'Password berhasil diubah!' : 'Terjadi kesalahan.'));

            if (data.success) form.reset();
        } catch {
            showMsg(msg, 'error', 'Gagal menghubungi server. Coba lagi.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Ganti Password';
        }
    });
}

// ── AVATAR UPLOAD ─────────────────────────────────────────
function initAvatarUpload() {
    const editBtn = document.getElementById('avatarEditBtn');
    const input = document.getElementById('avatarInput');
    const modal = document.getElementById('avatarModal');
    const cancelBtn = document.getElementById('avatarCancelBtn');
    const saveBtn = document.getElementById('avatarSaveBtn');
    const preview = document.getElementById('avatarPreview');
    const placeholder = document.getElementById('avatarPreviewPlaceholder');

    if (!editBtn || !input || !modal) return;

    let selectedFile = null;

    // Open modal via file picker
    editBtn.addEventListener('click', () => input.click());

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file maksimal 5MB.');
            input.value = '';
            return;
        }

        selectedFile = file;
        const reader = new FileReader();
        reader.onload = ev => {
            preview.src = ev.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
            saveBtn.disabled = false;
        };
        reader.readAsDataURL(file);
        modal.style.display = 'flex';
    });

    // Cancel
    cancelBtn?.addEventListener('click', closeAvatarModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeAvatarModal(); });

    function closeAvatarModal() {
        modal.style.display = 'none';
        input.value = '';
        selectedFile = null;
        saveBtn.disabled = true;
        preview.style.display = 'none';
        if (placeholder) placeholder.style.display = '';
    }

    // Save
    saveBtn?.addEventListener('click', async () => {
        if (!selectedFile) return;

        saveBtn.disabled = true;
        saveBtn.textContent = 'Mengupload...';

        const fd = new FormData();
        fd.append('action', 'update_avatar');
        fd.append('user_type', window.USER_TYPE);
        fd.append('user_id', window.USER_ID);
        fd.append('avatar', selectedFile);

        try {
            const res = await fetch('../api/update-profile.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                // Update semua avatar di halaman
                const newSrc = data.url + '?t=' + Date.now();
                const avatarImg = document.getElementById('avatarImg');
                const avatarInitials = document.getElementById('avatarInitials');
                const headerAvatar = document.querySelector('.profile-trigger .profile-avatar');

                if (avatarImg) {
                    avatarImg.src = newSrc;
                } else if (avatarInitials) {
                    // Ganti initials dengan gambar
                    const img = document.createElement('img');
                    img.className = 'avatar-large';
                    img.id = 'avatarImg';
                    img.src = newSrc;
                    img.alt = 'avatar';
                    avatarInitials.replaceWith(img);
                }

                if (headerAvatar) {
                    if (headerAvatar.tagName === 'IMG') {
                        headerAvatar.src = newSrc;
                    }
                }

                closeAvatarModal();
                showToast('Foto profil berhasil diperbarui!', 'success');
            } else {
                alert(data.message || 'Gagal mengupload foto.');
            }
        } catch {
            alert('Gagal menghubungi server.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Simpan Foto';
        }
    });
}

// ── HELPERS ───────────────────────────────────────────────
function showMsg(el, type, text) {
    el.className = 'form-msg ' + type;
    el.textContent = text;
    el.style.display = 'block';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function showToast(msg, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position:fixed; bottom:1.5rem; right:1.5rem;
        padding:.85rem 1.25rem; border-radius:.6rem; font-size:.9rem;
        font-weight:600; color:white; z-index:9999;
        box-shadow: 0 8px 24px rgba(0,0,0,.25);
        animation: slideUp .3s ease;
        background: ${type === 'success' ? '#22c55e' : '#ef4444'};
    `;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}

// Inject keyframe for toast
const style = document.createElement('style');
style.textContent = '@keyframes slideUp { from { transform:translateY(1rem); opacity:0; } to { transform:translateY(0); opacity:1; } }';
document.head.appendChild(style);

// ── INIT ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    applySavedTheme();
    initThemeToggle();
    initProfileDropdown();
    initTabs();
    initPasswordToggles();
    initEditProfileForm();
    initChangePasswordForm();
    initAvatarUpload();
});
