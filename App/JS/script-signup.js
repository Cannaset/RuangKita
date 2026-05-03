document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("#signupForm");
    const fullname = document.querySelector("#fullname");
    const nim = document.querySelector("#nim");
    const email = document.querySelector("#email");
    const password = document.querySelector("#password");
    const confirmPassword = document.querySelector("#confirmPassword");
    const errorMessage = document.querySelector("#error-message");

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const fullnameValue = fullname.value.trim();
        const nimValue = nim.value.trim();
        const emailValue = email.value.trim();
        const passwordValue = password.value.trim();
        const confirmPasswordValue = confirmPassword.value.trim();

        // Validation
        if (!fullnameValue || !nimValue || !emailValue || !passwordValue || !confirmPasswordValue) {
            showMessage("Semua field wajib diisi");
            return;
        }

        if (fullnameValue.length < 3) {
            showMessage("Nama lengkap minimal 3 karakter");
            return;
        }

        if (nimValue.length < 10) {
            showMessage("NIM harus minimal 10 karakter");
            return;
        }

        if (!emailValue.includes("@")) {
            showMessage("Email tidak valid");
            return;
        }

        if (passwordValue.length < 6) {
            showMessage("Password minimal 6 karakter");
            return;
        }

        if (passwordValue !== confirmPasswordValue) {
            showMessage("Password dan konfirmasi password tidak cocok");
            return;
        }

        // Demo: Just show success message
        // Nanti akan di-connect ke backend API
        showMessage("Pendaftaran berhasil! Silakan login.");
        errorMessage.style.color = "green";

        setTimeout(function () {
            window.location.href = "index.html";
        }, 2000);
    });

    function showMessage(text) {
        errorMessage.innerHTML = text;
        errorMessage.style.color = "#ef4444";
    }
});