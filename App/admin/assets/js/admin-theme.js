document.addEventListener("DOMContentLoaded", () => {
    const themeToggle = document.getElementById("themeToggle");
    const themeIcon = document.getElementById("themeIcon");

    function setTheme(isDark) {
        document.body.classList.toggle("dark-theme", isDark);
        document.documentElement.classList.toggle("dark-theme", isDark);

        if (themeIcon) {
            themeIcon.textContent = isDark ? "Light" : "Dark";
        }
    }

    function applySavedTheme() {
        setTheme(localStorage.getItem("ruangkita-theme") === "dark");
    }

    applySavedTheme();

    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            const isDark = !document.body.classList.contains("dark-theme");
            localStorage.setItem("ruangkita-theme", isDark ? "dark" : "light");
            setTheme(isDark);
        });
    }
});
