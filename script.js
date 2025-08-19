document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const settingsBtn = document.getElementById('settings-btn');
    const settingsModal = document.getElementById('settings-modal');
    const closeModal = document.querySelector('.close-modal');
    const logoutBtn = document.getElementById('logout-btn');
    
    if (settingsBtn) {
        settingsBtn.addEventListener('click', () => {
            settingsModal.style.display = 'flex';
        });
    }
    
    if (closeModal) {
        closeModal.addEventListener('click', () => {
            settingsModal.style.display = 'none';
        });
    }
    
    window.addEventListener('click', (e) => {
        if (e.target === settingsModal) {
            settingsModal.style.display = 'none';
        }
    });
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            window.location.href = '../auth/logout.php';
        });
    }
    
    // Password form submission
    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('Password must be at least 6 characters long!');
                return;
            }
            
            try {
                const res = await fetch('../auth/update_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                });
                
                const data = await res.json();
                alert(data.message);
                if (data.status === 'success') {
                    settingsModal.style.display = 'none';
                    passwordForm.reset();
                }
            } catch (err) {
                alert('Password update failed: ' + err.message);
                console.error(err);
            }
        });
    }
});