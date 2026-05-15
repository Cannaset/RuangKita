// ============================================
// DATA & STATE
// ============================================
const postsData = [
    {
        id: 1,
        author: 'Anonymous Student',
        initials: 'AS',
        timestamp: '2 hours ago',
        status: 'Completed',
        content: 'The library needs more charging stations for students. Many outlets are occupied and students struggle to find places to charge devices during study sessions.',
        upvotes: 42,
        comments: 12,
        hasImage: true,
        imageUrl: '../image/wimaw.jpeg',
        category: 'Facilities'
    },
    {
        id: 2,
        author: 'John Doe',
        initials: 'JD',
        timestamp: '4 hours ago',
        status: 'Unresolved',
        content: 'The cafeteria food quality has been declining. Please consider improving the meal options and freshness of ingredients.',
        upvotes: 28,
        comments: 8,
        hasImage: false,
        category: 'Facilities'
    },
    {
        id: 3,
        author: 'Sarah Mitchell',
        initials: 'SM',
        timestamp: '6 hours ago',
        status: 'Completed',
        content: 'The new academic building needs better signage. Students are having difficulty finding classrooms and offices.',
        upvotes: 35,
        comments: 5,
        hasImage: false,
        category: 'Academic'
    },
    {
        id: 4,
        author: 'Mike Johnson',
        initials: 'MJ',
        timestamp: '8 hours ago',
        status: 'Unresolved',
        content: 'The bathroom facilities need better cleaning schedules. They are not being maintained properly throughout the day.',
        upvotes: 19,
        comments: 3,
        hasImage: false,
        category: 'Cleanliness'
    },
    {
        id: 5,
        author: 'Emma Wilson',
        initials: 'EW',
        timestamp: '10 hours ago',
        status: 'Completed',
        content: 'WiFi connectivity in the dormitory is very poor. Please improve the network coverage for students.',
        upvotes: 56,
        comments: 15,
        hasImage: false,
        category: 'Facilities'
    }
];

let currentCategory = 'All';
let currentSort = 'Newest';

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
function handleVote(postId, voteType) {
    console.log(`Vote ${voteType} untuk post ${postId}`);
    updateVoteCountUI(postId, voteType);
}

function updateVoteCountUI(postId, voteType) {
    const voteCountEl = document.querySelector(`.vote-count-${postId}`);
    if (voteCountEl) {
        let currentCount = parseInt(voteCountEl.textContent);
        if (voteType === 'up') {
            voteCountEl.textContent = currentCount + 1;
        } else if (voteType === 'down') {
            voteCountEl.textContent = Math.max(0, currentCount - 1);
        }
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
                        <button class="interaction-btn vote-up" data-post-id="${post.id}">⬆</button>
                        <span class="vote-count-${post.id}">${post.upvotes}</span>
                        <button class="interaction-btn vote-down" data-post-id="${post.id}">⬇</button>
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
// FILTER & SORT
// ============================================
function filterAndSortPosts() {
    let filtered = [...postsData];

    if (currentCategory !== 'All') {
        filtered = filtered.filter(post => post.category === currentCategory);
    }

    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter(post =>
            post.content.toLowerCase().includes(searchTerm) ||
            post.author.toLowerCase().includes(searchTerm)
        );
    }

    if (currentSort === 'Popular') {
        filtered.sort((a, b) => b.upvotes - a.upvotes);
    } else if (currentSort === 'Newest') {
        // Keep original order
    } else if (currentSort === 'Unresolved') {
        filtered.sort((a, b) => {
            if (a.status === 'Unresolved' && b.status !== 'Unresolved') return -1;
            if (a.status !== 'Unresolved' && b.status === 'Unresolved') return 1;
            return 0;
        });
    }

    const container = document.getElementById('feedContainer');
    if (!container) return;

    container.innerHTML = filtered.length > 0
        ? filtered.map(post => createPostHTML(post)).join('')
        : '<p style="text-align: center; color: var(--gray-500); padding: 2rem;">No posts found</p>';

    attachEventListeners();
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