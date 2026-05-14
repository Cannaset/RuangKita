document.addEventListener("DOMContentLoaded", function () {
    // ============================================
    // ELEMENTS
    // ============================================
    const form = document.querySelector("#signupForm");
    const usernameInput = document.querySelector("#username");
    const nimInput = document.querySelector("#nim");
    const emailInput = document.querySelector("#email");
    const passwordInput = document.querySelector("#password");
    const confirmPasswordInput = document.querySelector("#confirmPassword");
    const errorMessage = document.querySelector("#signup-message");
    const themeToggle = document.querySelector("#themeToggle");
    const themeIcon = document.querySelector("#themeIcon");

    // ============================================
    // THEME FUNCTIONS
    // ============================================
    function applySavedTheme() {
        const savedTheme = localStorage.getItem("ruangkita-theme");
        const isDark = savedTheme === "dark";
        document.body.classList.toggle("dark-theme", isDark);
        document.documentElement.classList.toggle("dark-theme", isDark);
        updateThemeIcon(isDark);
    }

    function updateThemeIcon(isDark) {
        if (themeIcon) {
            themeIcon.textContent = isDark ? "Light" : "Dark";
        }
    }

    function toggleTheme() {
        const isDark = document.body.classList.toggle("dark-theme");
        document.documentElement.classList.toggle("dark-theme", isDark);
        localStorage.setItem("ruangkita-theme", isDark ? "dark" : "light");
        updateThemeIcon(isDark);
    }

    // ============================================
    // FORM FUNCTIONS
    // ============================================
    function showMessage(text, isError = true) {
        if (!errorMessage) return;
        errorMessage.innerHTML = text;
        errorMessage.style.color = isError ? "#ef4444" : "#16a34a";
    }

    function validateForm() {
        const username = usernameInput ? usernameInput.value.trim() : "";
        const nim = nimInput ? nimInput.value.trim() : "";
        const email = emailInput ? emailInput.value.trim() : "";
        const password = passwordInput ? passwordInput.value.trim() : "";
        const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value.trim() : "";

        // All required
        if (!username || !nim || !email || !password || !confirmPassword) {
            showMessage("Semua kolom wajib diisi", true);
            return false;
        }

        // Min length checks
        if (username.length < 3) {
            showMessage("Username minimal 3 karakter", true);
            return false;
        }

        if (nim.length < 8) {
            showMessage("NIM minimal 8 digit", true);
            return false;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showMessage("Format email tidak valid", true);
            return false;
        }

        // Password checks
        if (password.length < 6) {
            showMessage("Password minimal 6 karakter", true);
            return false;
        }

        if (password !== confirmPassword) {
            showMessage("Konfirmasi password tidak sama", true);
            return false;
        }

        return true;
    }

    // ============================================
    // FORM SUBMIT HANDLER
    // ============================================
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            // Validate
            if (!validateForm()) {
                return;
            }

            showMessage("Mendaftar...", false);

            // Allow form submission to PHP
            // PHP (signup.php) akan handle validation di backend
            setTimeout(() => {
                form.submit();
            }, 500);
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