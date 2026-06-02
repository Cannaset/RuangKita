document.addEventListener("DOMContentLoaded", () => {
    const profileMenu = document.querySelector(".profile-menu");
    const profileTrigger = document.getElementById("profileTrigger");

    if (profileMenu && profileTrigger) {
        profileTrigger.addEventListener("click", (event) => {
            event.stopPropagation();
            const isOpen = profileMenu.classList.toggle("open");
            profileTrigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        document.addEventListener("click", (event) => {
            if (!profileMenu.contains(event.target)) {
                profileMenu.classList.remove("open");
                profileTrigger.setAttribute("aria-expanded", "false");
            }
        });
    }
});