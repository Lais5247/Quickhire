document.addEventListener("DOMContentLoaded", () => {
  // Tab switching functionality
  const tabs = document.querySelectorAll('.tab');
  const forms = document.querySelectorAll('.auth-form');
  
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      
      const tabName = tab.getAttribute('data-tab');
      forms.forEach(form => {
        form.classList.remove('active-form');
        if (form.id === `${tabName}-form`) {
          form.classList.add('active-form');
        }
      });
    });
  });

  // Registration handler
  const registerForm = document.getElementById('register-form');
  if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const name = document.getElementById('registerName').value;
      const email = document.getElementById('registerEmail').value;
      const password = document.getElementById('registerPassword').value;
      const role = document.getElementById('register-role').value;

      try {
        const res = await fetch('register.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, email, password, role })
        });

        const text = await res.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (jsonError) {
          console.error('JSON Parse Error:', jsonError, 'Response Text:', text);
          throw new Error('Invalid server response');
        }

        alert(data.status === 'success' ? 'Registration Successful!' : data.message);
      } catch (err) {
        alert('Registration failed: ' + err.message);
        console.error(err);
      }
    });
  }

  // Login handler
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const email = document.getElementById('loginEmail').value;
      const password = document.getElementById('loginPassword').value;

      try {
        const res = await fetch('login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password })
        });

        const text = await res.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (jsonError) {
          console.error('JSON Parse Error:', jsonError, 'Response Text:', text);
          throw new Error('Invalid server response');
        }

        if (data.status === 'success') {
          // Redirect to appropriate dashboard
          const role = data.user.role;
          let dashboard = '';
          
          switch(role) {
              case 'admin':
                  dashboard = '../admin_dashboard/index.php';
                  break;
              case 'homeowner':
                  dashboard = '../homeowner_dashboard/index.php';
                  break;
              case 'maid':
                  dashboard = '../maid_dashboard/index.php';
                  break;
              default:
                  dashboard = 'index.php';
          }
          
          window.location.href = dashboard;
      } else {
          alert('Login failed: ' + data.message);
      }

      } catch (err) {
        alert('Login failed: ' + err.message);
        console.error(err);
      }
    });
  }
});