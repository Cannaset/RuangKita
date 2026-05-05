document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.querySelector("#themeToggle");
    const themeIcon = document.querySelector("#themeIcon");

    // Demo credentials - nanti diganti dengan backend API call
    const correctNim = "2310110001";
    const correctPassword = "123456";

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const userNim = nim.value.trim();
        const userPassword = password.value.trim();

        if (userNim === "" && userPassword === "") {
            showMessage("NIM dan Password wajib diisi");
            return;
        }

        if (userNim === "") {
            showMessage("NIM tidak boleh kosong");
            return;
        }

        if (userPassword === "") {
            showMessage("Password tidak boleh kosong");
            return;
        }

        if (userNim !== correctNim) {
            showMessage("NIM tidak ditemukan");
            return;
        }

        if (userPassword !== correctPassword) {
            showMessage("Password salah");
            return;
        }

        showMessage("Login berhasil");
        errorMessage.style.color = "green";

        // Store session (demo - nanti dari backend)
        localStorage.setItem('user_nim', userNim);
        localStorage.setItem('user_name', 'John Doe');

        setTimeout(function () {
            window.location.href = "feed.php";
        }, 1000);
    applySavedTheme();

    themeToggle.addEventListener("click", function () {
        const isDark = document.body.classList.toggle("dark-theme");
        document.documentElement.classList.toggle("dark-theme", isDark);
        localStorage.setItem("ruangkita-theme", isDark ? "dark" : "light");
        updateThemeIcon(isDark);
    });

    function applySavedTheme() {
        const savedTheme = localStorage.getItem("ruangkita-theme");
        const isDark = savedTheme === "dark";
        document.body.classList.toggle("dark-theme", isDark);
        document.documentElement.classList.toggle("dark-theme", isDark);
        updateThemeIcon(isDark);
    }

    function updateThemeIcon(isDark) {
        themeIcon.textContent = isDark ? "Light" : "Dark";
    }
})});
