// ============================================
// DATA & STATE
// ============================================
let currentCategory = 'All';
let currentSort = 'Newest';
let cachedPosts = []; // menyimpan data dari API

// ============================================
// THEME MANAGEMENT
// ============================================
function applySavedTheme() {
    const savedTheme = localStorage.getItem('ruangkita-theme');
    const isDark = savedTheme === 'dark';
    applyTheme(isDark);
}

function applyTheme(isDark) {
    const body = document.body;
    const themeIcon = document.getElementById('themeIcon');
    const notificationIcon = document.getElementById('notificationIcon');

    if (isDark) {
        body.classList.add('dark-theme');
    } else {
        body.classList.remove('dark-theme');
    }

    if (themeIcon) {
        themeIcon.textContent = isDark ? 'Light' : 'Dark';
    }

    if (notificationIcon) {
        notificationIcon.src = isDark
            ? notificationIcon.dataset.darkSrc
            : notificationIcon.dataset.lightSrc;
    }
}

function initializeThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');

    if (!themeToggle) return;

    themeToggle.addEventListener('click', () => {
        const isDark = document.body.classList.toggle('dark-theme');
        localStorage.setItem('ruangkita-theme', isDark ? 'dark' : 'light');

        const themeIcon = document.getElementById('themeIcon');
        if (themeIcon) {
            themeIcon.textContent = isDark ? 'Light' : 'Dark';
        }

        const notificationIcon = document.getElementById('notificationIcon');
        if (notificationIcon) {
            notificationIcon.src = isDark
                ? notificationIcon.dataset.darkSrc
                : notificationIcon.dataset.lightSrc;
        }
    });
}

// ============================================
// PROFILE DROPDOWN
// ============================================
function initializeProfileDropdown() {
    const profileMenu = document.querySelector('.profile-menu');
    const profileTrigger = document.getElementById('profileTrigger');

    if (!profileMenu || !profileTrigger) return;

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
// MODAL FUNCTIONS
// ============================================
function openImageModal(imageUrl) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');

    if (!modal || !modalImage) return;

    modalImage.src = imageUrl;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');

    if (!modal) return;

    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function initializeModalListeners() {
    const modal = document.getElementById('imageModal');

    if (!modal) return;

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeImageModal();
        }
    });

    const closeBtn = document.querySelector('.modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeImageModal);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeImageModal();
        }
    });
}

// ============================================
// UTILITY FUNCTIONS
// ============================================
function getAvatarColor(initials) {
    const colors = ['color1', 'color2', 'color3', 'color4'];
    return colors[initials.charCodeAt(0) % colors.length];
}

function getStatusClass(status) {
    return status === 'Completed' ? 'status-completed' : 'status-unresolved';
}

// ============================================
// VOTE HANDLING
// ============================================
async function handleVote(postId, voteType) {
    try {
        const res = await fetch('../api/votes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: parseInt(postId), vote_type: voteType })
        });
        const data = await res.json();

        if (!data.success) {
            console.error('Vote gagal:', data.message);
            return;
        }

        // Ambil ulang data vote terbaru dari API lalu update UI
        const voteRes = await fetch(`../api/votes.php?post_id=${postId}`);
        const voteData = await voteRes.json();

        if (voteData.success) {
            const upEl = document.querySelector(`.vote-count-up-${postId}`);
            const downEl = document.querySelector(`.vote-count-down-${postId}`);
            const upBtn = document.querySelector(`.vote-up[data-post-id="${postId}"]`);
            const downBtn = document.querySelector(`.vote-down[data-post-id="${postId}"]`);

            if (upEl) upEl.textContent = voteData.upvotes;
            if (downEl) downEl.textContent = voteData.downvotes;

            // Highlight tombol yang aktif
            if (upBtn) upBtn.classList.toggle('active', voteData.my_vote === 'upvote');
            if (downBtn) downBtn.classList.toggle('active', voteData.my_vote === 'downvote');
        }
    } catch (err) {
        console.error('Error vote:', err);
    }
}

// ============================================
// EVENT LISTENERS - VOTES & MODAL
// ============================================
function attachEventListeners() {
    // Vote buttons
    document.querySelectorAll('.vote-up').forEach(btn => {
        btn.addEventListener('click', () => {
            const postId = btn.dataset.postId;
            handleVote(postId, 'upvote');
        });
    });

    document.querySelectorAll('.vote-down').forEach(btn => {
        btn.addEventListener('click', () => {
            const postId = btn.dataset.postId;
            handleVote(postId, 'downvote');
        });
    });

    // Image modal
    document.querySelectorAll('.post-image-clickable').forEach(img => {
        img.addEventListener('click', () => {
            const imageUrl = img.dataset.imageUrl;
            openImageModal(imageUrl);
        });
    });
}

// ============================================
// POST RENDERING
// ============================================
function createPostHTML(post) {
    return `
        <div class="post-card" data-post-id="${post.id}" data-is-owner="${post.is_owner === true ? 'true' : 'false'}">
            <div class="post-header-container">
                <div class="post-header">
                    <div class="post-user-info">
                        <div class="avatar ${getAvatarColor(post.initials)}">
                            ${post.initials}
                        </div>
                        <div class="post-user-details">
                            <h4>${post.author}</h4>
                            <small>${post.timestamp}</small>
                        </div>
                    </div>
                    <div class="post-actions-top">
                        <span class="status-badge ${getStatusClass(post.status)}">
                            ${post.status}
                        </span>
                        <button class="post-menu">⋯</button>
                    </div>
                </div>
                <div class="post-content">${post.content}</div>
                ${post.hasImage ? `
                    <div class="post-image post-image-clickable" data-image-url="${post.imageUrl}">
                        <img src="${post.imageUrl}" alt="Post image" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;">
                    </div>
                ` : ''}
            </div>

            <div class="post-footer">
                <div class="post-interactions">
                    <div class="interaction-item">
                        <button class="interaction-btn vote-up ${post.my_vote === 'upvote' ? 'active' : ''}" data-post-id="${post.id}">⬆</button>
                        <span class="vote-count-up-${post.id}">${post.upvotes}</span>
                        <button class="interaction-btn vote-down ${post.my_vote === 'downvote' ? 'active' : ''}" data-post-id="${post.id}">⬇</button>
                        <span class="vote-count-down-${post.id}">${post.downvotes}</span>
                    </div>
                    <div class="interaction-item">
                        <span>💬</span>
                        <span>${post.comments}</span>
                    </div>
                </div>
                <button class="share-btn">🔗</button>
            </div>
        </div>
    `;
}

// ============================================
// FETCH POSTS DARI API
// ============================================
async function fetchAndRenderPosts() {
    const container = document.getElementById('feedContainer');
    if (!container) return;

    // Tampilkan loading dulu
    container.innerHTML = '<p style="text-align:center;padding:2rem;color:var(--gray-500)">Memuat...</p>';

    // Bangun query params
    const params = new URLSearchParams();
    if (currentCategory !== 'All') params.set('category', currentCategory);
    if (currentSort === 'Popular') params.set('sort', 'popular');
    if (currentSort === 'Unresolved') params.set('sort', 'unresolved');

    const searchTerm = document.getElementById('searchInput')?.value.trim();
    if (searchTerm) params.set('search', searchTerm);

    try {
        const res = await fetch(`../api/posts.php?${params.toString()}`);
        const data = await res.json();

        if (!data.success) {
            container.innerHTML = '<p style="text-align:center;padding:2rem;color:var(--gray-500)">Gagal memuat post.</p>';
            return;
        }

        cachedPosts = data.data;

        container.innerHTML = cachedPosts.length > 0
            ? cachedPosts.map(post => createPostHTML(post)).join('')
            : '<p style="text-align:center;padding:2rem;color:var(--gray-500)">Belum ada post.</p>';

        attachEventListeners();

    } catch (err) {
        console.error('Fetch posts error:', err);
        container.innerHTML = '<p style="text-align:center;padding:2rem;color:var(--gray-500)">Terjadi kesalahan.</p>';
    }
}

// ============================================
// EVENT LISTENERS - FILTER & SORT
// ============================================
function initializeFiltersAndSort() {
    const filterNav = document.getElementById('filterNav');
    const sortNav = document.getElementById('sortNav');
    const searchInput = document.getElementById('searchInput');

    if (filterNav) {
        filterNav.addEventListener('click', (e) => {
            if (e.target.classList.contains('filter-btn')) {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                currentCategory = e.target.dataset.category;
                fetchAndRenderPosts();
            }
        });
    }

    if (sortNav) {
        sortNav.addEventListener('click', (e) => {
            if (e.target.classList.contains('sort-btn')) {
                document.querySelectorAll('.sort-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                currentSort = e.target.dataset.sort;
                fetchAndRenderPosts();
            }
        });
    }

    if (searchInput) {
        // Pakai debounce supaya tidak fetch tiap ketik 1 huruf
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchAndRenderPosts, 400);
        });
    }
}

// ============================================================
// PATCH: Fix Empty Buttons in script-feed.js
// Tambahkan kode ini ke bagian bawah script-feed.js
// (sebelum closing DOMContentLoaded atau setelah semua function)
// ============================================================

// ── 1. SHARE BUTTON → Copy link to clipboard + toast ──────
function initShareButtons() {
    document.addEventListener('click', e => {
        const btn = e.target.closest('.share-btn');
        if (!btn) return;

        const postCard = btn.closest('.post-card');
        const postId = postCard?.dataset.postId;
        const shareUrl = postId
            ? `${location.origin}${location.pathname}?post=${postId}`
            : location.href;

        navigator.clipboard.writeText(shareUrl)
            .then(() => showFeedToast('🔗 Link berhasil disalin!', 'success'))
            .catch(() => {
                // Fallback untuk browser yang tidak support clipboard API
                const tmp = document.createElement('input');
                tmp.value = shareUrl;
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                tmp.remove();
                showFeedToast('🔗 Link berhasil disalin!', 'success');
            });
    });
}

// ── 2. NOTIFICATION BUTTON → Panel notifikasi ──────────────
function initNotificationButton() {
    const btn = document.querySelector('.notification-btn');
    if (!btn) return;

    // Badge unread
    const badge = document.createElement('span');
    badge.id = 'notifBadge';
    badge.style.cssText = `
        position:absolute; top:-4px; right:-4px;
        background:#ef4444; color:white;
        font-size:.65rem; font-weight:700;
        border-radius:999px; padding:1px 5px;
        display:none;
    `;
    btn.style.position = 'relative';
    btn.appendChild(badge);

    // Panel
    const panel = document.createElement('div');
    panel.id = 'notifPanel';
    panel.style.cssText = `
        position:fixed; top:70px; right:1rem;
        width:320px; background:white;
        border-radius:.75rem;
        box-shadow:0 12px 40px rgba(0,0,0,.2);
        border:1px solid #e5e7eb;
        z-index:1500; display:none; overflow:hidden;
    `;
    panel.innerHTML = `
        <div style="padding:.9rem 1.1rem;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:.95rem;font-weight:700;color:#111827">Notifikasi</span>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <button id="notifMarkAll" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#008891;font-weight:700;">Tandai semua dibaca</button>
                <button id="notifClose" style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:#6b7280;">✕</button>
            </div>
        </div>
        <div id="notifList" style="max-height:360px;overflow-y:auto;padding:.5rem 0;">
            <div style="padding:2rem 1rem;text-align:center;color:#9ca3af;font-size:.875rem;">Memuat...</div>
        </div>
    `;
    document.body.appendChild(panel);

    // Dark mode styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        body.dark-theme #notifPanel { background:#0f172a !important; border-color:#334155 !important; }
        body.dark-theme #notifPanel span, body.dark-theme #notifPanel p { color:#f8fafc !important; }
    `;
    document.head.appendChild(style);

    // Fetch notifikasi
    async function loadNotifications() {
        try {
            const res = await fetch('../api/notifications.php');
            const data = await res.json();

            if (!data.success) return;

            // Update badge
            if (data.unread > 0) {
                badge.textContent = data.unread > 9 ? '9+' : data.unread;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }

            const list = document.getElementById('notifList');
            if (!list) return;

            if (!data.data?.length) {
                list.innerHTML = '<div style="padding:2rem 1rem;text-align:center;color:#9ca3af;font-size:.875rem;">🔔 Belum ada notifikasi</div>';
                return;
            }

            list.innerHTML = data.data.map(n => `
    <div class="notif-item" data-id="${n.id}" data-post-id="${n.post_id}" style="
        padding:.75rem 1.1rem;
        border-bottom:1px solid #f3f4f6;
        cursor:pointer;
        background:${n.is_read ? 'transparent' : '#f0fdfa'};
        display:flex; gap:.75rem; align-items:flex-start;
        transition: background .2s;
    ">
        <div style="font-size:1.1rem;margin-top:.1rem;flex-shrink:0;">
            ${{ status_change: '🔄', admin_response: '📣', comment: '💬' }[n.type] ?? '🔔'}
        </div>
        <div style="flex:1;min-width:0;">
            <p style="margin:0;font-size:.85rem;color:#111827;line-height:1.4;">${escHtml(n.message)}</p>
            <small style="color:#9ca3af;font-size:.75rem;">${timeAgoClient(n.created_at)}</small>
        </div>
        ${!n.is_read ? `<div style="width:8px;height:8px;border-radius:50%;background:#008891;margin-top:.35rem;flex-shrink:0;"></div>` : ''}
    </div>
`).join('');

            // Klik notif → mark read
            list.querySelectorAll('.notif-item').forEach(item => {
                item.addEventListener('click', async () => {
                    const id = item.dataset.id;
                    const postId = item.dataset.postId;

                    await fetch('../api/notifications.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'mark_read', id: parseInt(id) })
                    });

                    panel.style.display = 'none';
                    loadNotifications();
                    scrollToPost(postId);
                });
            });

        } catch (err) {
            console.error('Notif error:', err);
        }
    }

    // Mark all read
    document.getElementById('notifMarkAll')?.addEventListener('click', async () => {
        await fetch('../api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read' })
        });
        loadNotifications();
    });

    // Toggle panel
    btn.addEventListener('click', e => {
        e.stopPropagation();
        const isOpen = panel.style.display === 'block';
        panel.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) loadNotifications();
    });

    document.getElementById('notifClose')?.addEventListener('click', () => {
        panel.style.display = 'none';
    });

    document.addEventListener('click', e => {
        if (!panel.contains(e.target) && e.target !== btn) {
            panel.style.display = 'none';
        }
    });

    // Poll setiap 30 detik untuk update badge
    loadNotifications();
    setInterval(loadNotifications, 30000);
}

function timeAgoClient(datetime) {
    const now = new Date();
    const past = new Date(datetime.replace(' ', 'T'));
    const diff = Math.floor((now - past) / 1000);

    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return Math.floor(diff / 60) + ' menit yang lalu';
    if (diff < 86400) return Math.floor(diff / 3600) + ' jam yang lalu';
    return Math.floor(diff / 86400) + ' hari yang lalu';
}

async function scrollToPost(postId) {
    if (!postId) return;

    // Cek apakah post sudah ada di feed
    let card = document.querySelector(`.post-card[data-post-id="${postId}"]`);

    if (!card) {
        // Reset filter dulu biar semua post muncul
        currentCategory = 'All';
        currentSort = 'Newest';
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.filter-btn[data-category="All"]')?.classList.add('active');
        document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.sort-btn[data-sort="Newest"]')?.classList.add('active');

        await fetchAndRenderPosts();
        card = document.querySelector(`.post-card[data-post-id="${postId}"]`);
    }

    if (!card) {
        showFeedToast('Post ini belum tersedia di beranda.', 'error');
        return;
    }

    // Scroll ke post
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Highlight effect
    card.style.transition = 'box-shadow .3s, outline .3s';
    card.style.outline = '2px solid #008891';
    card.style.boxShadow = '0 0 0 4px rgba(0,136,145,.15)';

    setTimeout(() => {
        card.style.outline = '';
        card.style.boxShadow = '';
    }, 2500);
}

// ── 3. POST MENU (⋯) → Dropdown dengan opsi ───────────────
function initPostMenus() {
    // Hapus menu lama kalau ada
    const closeAllMenus = () => {
        document.querySelectorAll('.post-menu-dropdown').forEach(d => d.remove());
    };

    document.addEventListener('click', e => {
        const btn = e.target.closest('.post-menu');
        if (!btn) {
            closeAllMenus();
            return;
        }

        e.stopPropagation();
        closeAllMenus();

        const postCard = btn.closest('.post-card');
        const postId = postCard?.dataset.postId ?? '0';
        const isOwner = postCard?.dataset.isOwner === 'true';

        const dropdown = document.createElement('div');
        dropdown.className = 'post-menu-dropdown';
        dropdown.style.cssText = `
            position: absolute;
            right: 0; top: calc(100% + 4px);
            min-width: 160px;
            background: white;
            border-radius: .5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
            z-index: 500;
            overflow: hidden;
            animation: slideDown .15s ease;
        `;

        const menuItems = [];

        // Copy link (semua user bisa)
        menuItems.push({ icon: '🔗', label: 'Salin Link', action: 'copy' });

        if (isOwner) {
            // Owner bisa delete post-nya
            menuItems.push({ icon: '🗑️', label: 'Hapus Aspirasi', action: 'delete', danger: true });
        } else {
            // Non-owner bisa report
            menuItems.push({ icon: '🚩', label: 'Laporkan', action: 'report', danger: false });
        }

        dropdown.innerHTML = menuItems.map(item => `
            <button
                data-action="${item.action}"
                data-post-id="${postId}"
                style="
                    display:flex; align-items:center; gap:.6rem;
                    width:100%; padding:.7rem 1rem;
                    background:none; border:none; cursor:pointer;
                    font-size:.875rem; font-weight:600;
                    color:${item.danger ? '#ef4444' : '#374151'};
                    text-align:left;
                    transition: background .15s;
                "
                onmouseover="this.style.background='#f3f4f6'"
                onmouseout="this.style.background='none'"
            >
                <span>${item.icon}</span> ${item.label}
            </button>
        `).join('');

        // Wrap btn biar position:relative
        btn.style.position = 'relative';
        btn.appendChild(dropdown);

        // Handle clicks inside dropdown
        dropdown.addEventListener('click', e => {
            e.stopPropagation();
            const actionBtn = e.target.closest('[data-action]');
            if (!actionBtn) return;

            const action = actionBtn.dataset.action;
            const pid = actionBtn.dataset.postId;
            closeAllMenus();

            if (action === 'copy') {
                const url = `${location.origin}${location.pathname}?post=${pid}`;
                navigator.clipboard.writeText(url)
                    .then(() => showFeedToast('🔗 Link berhasil disalin!', 'success'))
                    .catch(() => showFeedToast('Gagal menyalin link.', 'error'));

            } else if (action === 'delete') {
                if (confirm('Yakin ingin menghapus aspirasi ini?')) {
                    handleDeletePost(pid);
                }

            } else if (action === 'report') {
                handleReportPost(pid);
            }
        });
    });

    // Inject dark mode styles untuk dropdown
    const style = document.createElement('style');
    style.textContent = `
        body.dark-theme .post-menu-dropdown,
        body.feed-page.dark-theme .post-menu-dropdown {
            background: #0f172a !important;
            border-color: #334155 !important;
        }
        body.dark-theme .post-menu-dropdown button,
        body.feed-page.dark-theme .post-menu-dropdown button {
            color: #e2e8f0 !important;
        }
        body.dark-theme .post-menu-dropdown button[data-action="delete"],
        body.feed-page.dark-theme .post-menu-dropdown button[data-action="delete"] {
            color: #f87171 !important;
        }
    `;
    document.head.appendChild(style);
}

async function handleDeletePost(postId) {
    try {
        const res = await fetch('../api/posts.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: parseInt(postId) })
        });
        const text = await res.text();
        const data = JSON.parse(text);

        if (data.success) {
            const card = document.querySelector(`.post-card[data-post-id="${postId}"]`);
            if (card) {
                card.style.transition = 'opacity .3s, transform .3s';
                card.style.opacity = '0';
                card.style.transform = 'translateY(-8px)';
                setTimeout(() => card.remove(), 300);
            }
            showFeedToast('Aspirasi berhasil dihapus.', 'success');
        } else {
            showFeedToast(data.message || 'Gagal menghapus.', 'error');
        }
    } catch (err) {
        console.error('Delete error:', err);
        console.error('Error message:', err.message);
        console.error('Error stack:', err.stack);
        showFeedToast('Gagal menghubungi server.', 'error');
    }
}

function handleReportPost(postId) {
    // Simple report: bisa dikembangkan jadi modal nanti
    const reason = prompt('Alasan pelaporan (opsional):');
    if (reason === null) return; // user cancel

    showFeedToast('🚩 Laporan berhasil dikirim. Terima kasih!', 'success');

    // TODO: kirim ke API ketika endpoint siap
    // fetch('../api/reports.php', { method: 'POST', body: JSON.stringify({ post_id: postId, reason }) })
}

// ── 4. COMMENT BUTTON → Profil link fix ────────────────────
// Link "Profil" di dropdown sekarang sudah diarahkan ke profile.php di HTML
// Tambahkan juga fix untuk comment button (bisa expand comments nantinya)
function initCommentButtons() {
    document.addEventListener('click', e => {
        const commentItem = e.target.closest('.interaction-item');
        if (!commentItem) return;

        // Cek apakah ini comment item (ada emoji 💬)
        if (!commentItem.querySelector('span')?.textContent.includes('💬')) return;

        const postCard = commentItem.closest('.post-card');
        const postId = postCard?.dataset.postId;

        if (!postId) return;

        // Cek apakah comment section sudah ada
        let commentSection = postCard.querySelector('.comment-section');

        if (commentSection) {
            // Toggle
            const isHidden = commentSection.style.display === 'none';
            commentSection.style.display = isHidden ? 'block' : 'none';
            return;
        }

        // Buat comment section sederhana
        commentSection = document.createElement('div');
        commentSection.className = 'comment-section';
        commentSection.style.cssText = `
            border-top: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        `;
        commentSection.innerHTML = `
            <div class="comment-list" id="comments-${postId}" style="margin-bottom:.75rem;display:flex;flex-direction:column;gap:.5rem;">
                <p style="font-size:.85rem;color:#9ca3af;text-align:center;padding:.5rem 0;">Memuat komentar...</p>
            </div>
            <div style="display:flex;gap:.5rem;">
                <input
                    type="text"
                    class="comment-input"
                    placeholder="Tulis komentar..."
                    style="
                        flex:1; padding:.55rem .85rem;
                        border:1px solid #d1d5db; border-radius:.5rem;
                        font-size:.875rem; outline:none;
                        transition: border-color .2s;
                    "
                    data-post-id="${postId}"
                >
                <button
                    class="comment-submit-btn"
                    data-post-id="${postId}"
                    style="
                        padding:.55rem 1rem; background:#008891; color:white;
                        border:none; border-radius:.5rem; font-size:.875rem;
                        font-weight:700; cursor:pointer;
                        transition: background .2s;
                    "
                >Kirim</button>
            </div>
        `;

        const footer = postCard.querySelector('.post-footer');
        if (footer) footer.after(commentSection);
        else postCard.appendChild(commentSection);

        // Apply dark mode to new elements
        if (document.body.classList.contains('dark-theme')) {
            applyDarkToCommentSection(commentSection);
        }

        // Fetch comments
        fetchComments(postId);

        // Submit handler
        commentSection.querySelector('.comment-submit-btn')?.addEventListener('click', () => {
            const input = commentSection.querySelector('.comment-input');
            const content = input?.value.trim();
            if (!content) return;
            submitComment(postId, content, input, commentSection.querySelector(`#comments-${postId}`));
        });

        commentSection.querySelector('.comment-input')?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                commentSection.querySelector('.comment-submit-btn')?.click();
            }
        });
    });
}

async function fetchComments(postId) {
    const list = document.getElementById(`comments-${postId}`);
    if (!list) return;

    try {
        const res = await fetch(`../api/posts.php?action=comments&post_id=${postId}`);
        const data = await res.json();

        if (!data.success || !data.comments?.length) {
            list.innerHTML = '<p style="font-size:.85rem;color:#9ca3af;text-align:center;padding:.5rem 0;">Belum ada komentar. Jadilah yang pertama!</p>';
            return;
        }

        list.innerHTML = data.comments.map(c => `
            <div style="display:flex;gap:.6rem;align-items:flex-start;">
                <div style="
                    width:28px;height:28px;border-radius:50%;flex-shrink:0;
                    background:#008891;color:white;font-size:.7rem;font-weight:700;
                    display:flex;align-items:center;justify-content:center;
                ">${(c.author ?? 'U').charAt(0).toUpperCase()}</div>
                <div style="flex:1;min-width:0;">
                    <span style="font-size:.8rem;font-weight:700;color:#374151">${escHtml(c.author ?? 'Anonim')}</span>
                    <span style="font-size:.7rem;color:#9ca3af;margin-left:.4rem">${c.time_ago ?? ''}</span>
                    <p style="margin:.2rem 0 0;font-size:.85rem;color:#4b5563">${escHtml(c.content)}</p>
                </div>
            </div>
        `).join('');
    } catch {
        list.innerHTML = '<p style="font-size:.85rem;color:#9ca3af;text-align:center;">Gagal memuat komentar.</p>';
    }
}

async function submitComment(postId, content, inputEl, listEl) {
    const btn = inputEl?.nextElementSibling;
    if (btn) { btn.disabled = true; btn.textContent = '...'; }

    try {
        const res = await fetch('../api/posts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'comment', post_id: parseInt(postId), content })
        });
        const data = await res.json();

        if (data.success) {
            inputEl.value = '';
            fetchComments(postId);

            // Update comment count badge
            const card = document.querySelector(`.post-card[data-post-id="${postId}"]`);
            const countEl = card?.querySelector('.comment-count');
            if (countEl) countEl.textContent = parseInt(countEl.textContent || '0') + 1;
        } else {
            showFeedToast(data.message || 'Gagal mengirim komentar.', 'error');
        }
    } catch {
        showFeedToast('Gagal menghubungi server.', 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Kirim'; }
    }
}

function applyDarkToCommentSection(section) {
    section.style.borderColor = '#334155';
    const input = section.querySelector('.comment-input');
    if (input) {
        input.style.background = '#1e293b';
        input.style.borderColor = '#334155';
        input.style.color = '#f8fafc';
    }
}

function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── 5. TOAST ──────────────────────────────────────────────
function showFeedToast(msg, type = 'success') {
    // Hapus toast lama kalau ada
    document.querySelector('.feed-toast')?.remove();

    const toast = document.createElement('div');
    toast.className = 'feed-toast';
    toast.style.cssText = `
        position: fixed; bottom: 1.5rem; right: 1.5rem;
        padding: .85rem 1.25rem;
        border-radius: .6rem;
        font-size: .9rem; font-weight: 600;
        color: white; z-index: 9999;
        box-shadow: 0 8px 24px rgba(0,0,0,.25);
        animation: toastSlide .3s ease;
        background: ${type === 'success' ? '#22c55e' : '#ef4444'};
        max-width: 300px;
    `;
    toast.textContent = msg;

    if (!document.querySelector('#feedToastStyle')) {
        const s = document.createElement('style');
        s.id = 'feedToastStyle';
        s.textContent = '@keyframes toastSlide { from { opacity:0; transform:translateY(1rem); } to { opacity:1; transform:translateY(0); } }';
        document.head.appendChild(s);
    }

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(1rem)';
        toast.style.transition = 'opacity .3s, transform .3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ── INIT ALL FIXES ─────────────────────────────────────────
// Pastikan DOMContentLoaded sudah jalan, cukup panggil langsung
// atau tambahkan ke DOMContentLoaded yang sudah ada di script-feed.js
document.addEventListener('DOMContentLoaded', () => {
    applySavedTheme();
    initializeThemeToggle();
    initializeProfileDropdown();
    initializeModalListeners();
    initializeFiltersAndSort();
    fetchAndRenderPosts();

    initShareButtons();
    initNotificationButton();
    initPostMenus();
    initCommentButtons();
});