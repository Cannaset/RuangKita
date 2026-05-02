document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.querySelector("#themeToggle");
    const themeIcon = document.querySelector("#themeIcon");

    applySavedTheme();

    themeToggle.addEventListener("click", function () {
        const isDark = document.body.classList.toggle("dark-theme");
        localStorage.setItem("ruangkita-theme", isDark ? "dark" : "light");
        updateThemeIcon(isDark);
    });

    function applySavedTheme() {
        const savedTheme = localStorage.getItem("ruangkita-theme");
        const isDark = savedTheme === "dark";
        document.body.classList.toggle("dark-theme", isDark);
        updateThemeIcon(isDark);
    }

    function updateThemeIcon(isDark) {
        themeIcon.textContent = isDark ? "Light" : "Dark";
    }
});
