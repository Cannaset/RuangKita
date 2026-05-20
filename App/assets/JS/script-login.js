document.addEventListener("DOMContentLoaded", function () {
    // ============================================
    // ELEMENTS
    // ============================================
    const form = document.querySelector("#loginForm");
    const nimInput = document.querySelector("#nim");
    const passwordInput = document.querySelector("#password");
    const errorMessage = document.querySelector("#error-message");
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

    function validateForm(nim, password) {
        if (nim === "" && password === "") {
            showMessage("NIM dan Password wajib diisi", true);
            return false;
        }

        if (nim === "") {
            showMessage("NIM tidak boleh kosong", true);
            return false;
        }

        if (password === "") {
            showMessage("Password tidak boleh kosong", true);
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

            const nim = nimInput ? nimInput.value.trim() : "";
            const password = passwordInput ? passwordInput.value.trim() : "";

            // Validate
            if (!validateForm(nim, password)) {
                return;
            }

            // Form akan disubmit ke backend (PHP handle di index.php)
            // Kalau validation perlu di FE, tambah di sini
            
            showMessage("Logging in...", false);
            
            // Allow form submission to PHP
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