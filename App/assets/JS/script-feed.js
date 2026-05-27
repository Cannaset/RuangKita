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
            const upEl   = document.querySelector(`.vote-count-up-${postId}`);
            const downEl = document.querySelector(`.vote-count-down-${postId}`);
            const upBtn  = document.querySelector(`.vote-up[data-post-id="${postId}"]`);
            const downBtn = document.querySelector(`.vote-down[data-post-id="${postId}"]`);

            if (upEl)   upEl.textContent   = voteData.upvotes;
            if (downEl) downEl.textContent = voteData.downvotes;

            // Highlight tombol yang aktif
            if (upBtn)   upBtn.classList.toggle('active', voteData.my_vote === 'upvote');
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
            handleVote(postId, 'up');
        });
    });

    document.querySelectorAll('.vote-down').forEach(btn => {
        btn.addEventListener('click', () => {
            const postId = btn.dataset.postId;
            handleVote(postId, 'down');
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
        <div class="post-card">
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
    if (currentSort === 'Popular')    params.set('sort', 'popular');
    if (currentSort === 'Unresolved') params.set('sort', 'unresolved');

    const searchTerm = document.getElementById('searchInput')?.value.trim();
    if (searchTerm) params.set('search', searchTerm);

    try {
        const res  = await fetch(`../api/posts.php?${params.toString()}`);
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
    const filterNav   = document.getElementById('filterNav');
    const sortNav     = document.getElementById('sortNav');
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

// ============================================
// INITIALIZE ON DOM READY
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    applySavedTheme();
    initializeThemeToggle();
    initializeProfileDropdown();
    initializeModalListeners();
    initializeFiltersAndSort();

    // Fetch dari API, bukan data dummy
    fetchAndRenderPosts();
});

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
                filterAndSortPosts();
            }
        });
    }

    if (sortNav) {
        sortNav.addEventListener('click', (e) => {
            if (e.target.classList.contains('sort-btn')) {
                document.querySelectorAll('.sort-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                currentSort = e.target.dataset.sort;
                filterAndSortPosts();
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterAndSortPosts);
    }
}

// ============================================
// INITIALIZE ON DOM READY
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Theme setup FIRST (before rendering)
    applySavedTheme();
    initializeThemeToggle();

    // Then initialize other features
    initializeProfileDropdown();
    initializeModalListeners();
    initializeFiltersAndSort();

    // Finally render posts
    filterAndSortPosts();
});