import { saveCookie } from "./utility.js";

document.getElementById('registerForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const alertBox = document.getElementById('alertMessage');
    alertBox.style.display = 'none';

    const passwordInput = document.getElementById('password').value

    const hasMinLength = passwordInput.length >= 8;
    const hasNumber = /\d/.test(passwordInput)
    const hasSymbol = /[^a-zA-Z0-9\s]/.test(passwordInput)

    if (!hasMinLength || !hasNumber || !hasSymbol) {
        alertBox.className = 'alert alert-error';
        alertBox.textContent = 'Password must be at least 8 characters long, include a number, and a special symbol!';
        alertBox.style.display = 'block';
        return;
      }


    const payload = {
        firstName: document.getElementById('firstName').value.trim(),
        lastName: document.getElementById('lastName').value.trim(),
        login: document.getElementById('login').value.trim(),
        password: passwordInput
    };

    try {
        // Adjust relative path to point to your PHP registration endpoint file
        const response = await fetch('api/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok) {
            alertBox.className = 'alert alert-success';
            alertBox.textContent = data.message || 'Successfully registered!';
            alertBox.style.display = 'block';
            saveCookie(data.data.firstName, data.data.lastName, data.data.id)

            // Optionally redirect after success
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1500);
        } else {
            alertBox.className = 'alert alert-error';
            alertBox.textContent = data.message || 'Registration failed.';
            alertBox.style.display = 'block';
        }
    } catch (error) {
        alertBox.className = 'alert alert-error';
        alertBox.textContent = 'Network error or server unavailable.';
        alertBox.style.display = 'block';
    }
});
