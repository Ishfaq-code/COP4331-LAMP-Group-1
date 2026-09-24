import { saveCookie } from "./utility";

   document.getElementById('loginForm').addEventListener('submit', async function(e) {
      e.preventDefault();

      const alertBox = document.getElementById('alertMessage');
      alertBox.style.display = 'none';

      const loginInput = document.getElementById('login').value.trim();
      const passwordInput = document.getElementById('password').value;

      const payload = {
        login: loginInput,
        password: passwordInput
      };

      try {
        // Points directly to your existing PHP login file
        const response = await fetch('api/login.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok) {
          alertBox.className = 'alert alert-success';
          alertBox.textContent = data.message || 'Successfully logged in!';
          alertBox.style.display = 'block';

          saveCookie(data.data.firstName, data.data.lastName, data.data.id)

          // Redirect to the dashboard
          setTimeout(() => {
            window.location.href = 'dashboard.html';
          }, 1000);
        } else {
          // Display backend error message in the alert box
          alertBox.className = 'alert alert-error';
          alertBox.textContent = data.message || 'Login failed.';
          alertBox.style.display = 'block';
        }
      } catch (error) {
        alertBox.className = 'alert alert-error';
        alertBox.textContent = 'Network error or server unavailable.';
        alertBox.style.display = 'block';
      }
    });