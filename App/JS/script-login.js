document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("#loginForm");
    const nim = document.querySelector("#nim");
    const password = document.querySelector("#password");
    const errorMessage = document.querySelector("#error-message");

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
            window.location.href = "feed.html";
        }, 1000);
    });

    function showMessage(text) {
        errorMessage.innerHTML = text;
        errorMessage.style.color = "#ef4444";
    }
});