/**
 * RuangKita API Integration Utility
 * 
 * Structure:
 * - Config: Base URL dan API endpoints
 * - Helper methods: fetch wrapper, error handling
 * - API Services: organized by resource (posts, votes, comments)
 * 
 * Usage:
 * const api = new RuangKitaAPI();
 * await api.posts.create(data);
 */

class RuangKitaAPI {
    constructor() {
        // TODO: Update dengan production URL
        this.baseURL = "../api";
        
        this.endpoints = {
            posts: {
                list: "/posts.php",
                create: "/posts.php",
                detail: "/posts.php?id=:id",
                update: "/posts.php?id=:id",
                delete: "/posts.php?id=:id"
            },
            votes: {
                create: "/votes.php",
                delete: "/votes.php"
            },
            comments: {
                list: "/posts/:postId/comments",
                create: "/posts/:postId/comments",
                delete: "/comments/:id"
            },
            auth: {
                login: "/auth/login",
                logout: "/auth/logout",
                me: "/auth/me"
            }
        };
    }

    /**
     * Generic fetch wrapper dengan error handling
     */
    async request(method, endpoint, data = null) {
        try {
            const options = {
                method,
                headers: {
                    "Content-Type": "application/json",
                },
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(`${this.baseURL}${endpoint}`, options);

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || `HTTP Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error("API Error:", error);
            throw error;
        }
    }

    /**
     * Upload file dengan FormData
     */
    async uploadFile(endpoint, file, additionalData = {}) {
        try {
            const formData = new FormData();
            formData.append("file", file);
            
            Object.keys(additionalData).forEach(key => {
                formData.append(key, additionalData[key]);
            });

            const response = await fetch(`${this.baseURL}${endpoint}`, {
                method: "POST",
                body: formData,
                // Note: Don't set Content-Type header, browser akan set dengan boundary
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || `HTTP Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error("Upload Error:", error);
            throw error;
        }
    }

    // ============================================
    // POSTS SERVICE
    // ============================================
    posts = {
        /**
         * Get all posts with filters
         * @param {Object} filters - { category, sort, search, page }
         */
        list: async (filters = {}) => {
            const params = new URLSearchParams(filters).toString();
            return this.request("GET", `${this.endpoints.posts.list}?${params}`);
        },

        /**
         * Get single post by ID
         */
        getById: async (id) => {
            return this.request("GET", this.endpoints.posts.detail.replace(":id", id));
        },

        /**
         * Create new post
         * @param {Object} postData - { title, content, category, image, anonymous }
         */
        create: async (postData) => {
            // TODO: Update ini dengan actual API endpoint
            console.log("📝 [API PLACEHOLDER] Create Post:", postData);
            
            // Simulated response untuk demo
            return new Promise((resolve) => {
                setTimeout(() => {
                    resolve({
                        success: true,
                        message: "Post created successfully",
                        data: {
                            id: Date.now(),
                            ...postData,
                            createdAt: new Date().toISOString()
                        }
                    });
                }, 500);
            });
        },

        /**
         * Update post
         */
        update: async (id, postData) => {
            console.log("✏️ [API PLACEHOLDER] Update Post:", id, postData);
            return this.request("PUT", 
                this.endpoints.posts.update.replace(":id", id), 
                postData
            );
        },

        /**
         * Delete post
         */
        delete: async (id) => {
            console.log("🗑️ [API PLACEHOLDER] Delete Post:", id);
            return this.request("DELETE", 
                this.endpoints.posts.delete.replace(":id", id)
            );
        },

        /**
         * Update post status
         */
        updateStatus: async (id, status) => {
            console.log("📊 [API PLACEHOLDER] Update Status:", id, status);
            return this.request("PATCH", 
                `${this.endpoints.posts.update.replace(":id", id)}/status`,
                { status }
            );
        }
    };

    // ============================================
    // VOTES SERVICE
    // ============================================
    votes = {
        /**
         * Create vote (upvote/downvote)
         */
        create: async (postId, voteType) => {
            console.log("👍 [API PLACEHOLDER] Create Vote:", postId, voteType);
            return new Promise((resolve) => {
                setTimeout(() => {
                    resolve({
                        success: true,
                        data: { postId, voteType }
                    });
                }, 200);
            });
        },

        /**
         * Delete vote
         */
        delete: async (postId, voteId) => {
            console.log("❌ [API PLACEHOLDER] Delete Vote:", postId, voteId);
            return this.request("DELETE", 
                this.endpoints.votes.delete
                    .replace(":postId", postId)
                    .replace(":voteId", voteId)
            );
        }
    };

    // ============================================
    // COMMENTS SERVICE
    // ============================================
    comments = {
        /**
         * Get comments for a post
         */
        list: async (postId) => {
            console.log("💬 [API PLACEHOLDER] Get Comments:", postId);
            return this.request("GET", 
                this.endpoints.comments.list.replace(":postId", postId)
            );
        },

        /**
         * Create comment
         */
        create: async (postId, commentData) => {
            console.log("💬 [API PLACEHOLDER] Create Comment:", postId, commentData);
            return this.request("POST", 
                this.endpoints.comments.create.replace(":postId", postId),
                commentData
            );
        },

        /**
         * Delete comment
         */
        delete: async (commentId) => {
            console.log("❌ [API PLACEHOLDER] Delete Comment:", commentId);
            return this.request("DELETE", 
                this.endpoints.comments.delete.replace(":id", commentId)
            );
        }
    };

    // ============================================
    // AUTH SERVICE
    // ============================================
    auth = {
        /**
         * Get current user
         */
        getMe: async () => {
            return this.request("GET", this.endpoints.auth.me);
        },

        /**
         * Logout
         */
        logout: async () => {
            return this.request("POST", this.endpoints.auth.logout);
        }
    };
}

// ============================================
// USAGE EXAMPLES (Uncomment untuk test)
// ============================================

/*
const api = new RuangKitaAPI();

// Create post
(async () => {
    try {
        const result = await api.posts.create({
            title: "Test Post",
            content: "This is a test",
            category: "Facilities",
            anonymous: false
        });
        console.log("Result:", result);
    } catch (error) {
        console.error("Error:", error);
    }
})();

// Get posts
(async () => {
    try {
        const posts = await api.posts.list({ 
            category: "Facilities",
            sort: "newest"
        });
        console.log("Posts:", posts);
    } catch (error) {
        console.error("Error:", error);
    }
})();

// Create vote
(async () => {
    try {
        const vote = await api.votes.create(1, "upvote");
        console.log("Vote:", vote);
    } catch (error) {
        console.error("Error:", error);
    }
})();
*/

// Export untuk use di file lain
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RuangKitaAPI;
}
