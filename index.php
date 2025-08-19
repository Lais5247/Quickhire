<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Quickhire | Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../reset.css" />
  <link rel="stylesheet" href="../main.css" />
  <link rel="stylesheet" href="./index.css" />
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <div class="logo">
          <i class="fas fa-bolt"></i>
          <h1>Quickhire</h1>
        </div>
        <p class="tagline">Connect with trusted home service professionals</p>
      </div>

      <div class="form-container">
        <div class="form-tabs">
          <button class="tab active" data-tab="login">Login</button>
          <button class="tab" data-tab="register">Register</button>
        </div>

        <!-- Login Form -->
        <form id="login-form" class="auth-form active-form">
          <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" id="loginEmail" name="email" placeholder="Email address" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" id="loginPassword" name="password" placeholder="Password" required>
          </div>
          
          <button type="submit" class="btn btn-login">
            <i class="fas fa-sign-in-alt"></i> Sign In
          </button>
        </form>

        <!-- Registration Form -->
        <form id="register-form" class="auth-form">
          <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" id="registerName" name="name" placeholder="Full name" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" id="registerEmail" name="email" placeholder="Email address" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" id="registerPassword" name="password" placeholder="Password" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-user-tag"></i>
            <select id="register-role" name="role" aria-label="Role" required>
              <option value="">Select your role</option>
              <option value="homeowner">Homeowner 🏠</option>
              <option value="maid">Maid 🧹</option>
              <option value="admin">Admin 🛠️</option>
            </select>
          </div>
          
          <button type="submit" class="btn btn-register">
            <i class="fas fa-user-plus"></i> Create Account
          </button>
        </form>
      </div>

      <div class="auth-footer">
        <p>© 2025 Quickhire. All rights reserved.</p>
      </div>
    </div>
    
    <div class="welcome-panel">
      <div class="welcome-content">
        <h2>Welcome to Quickhire</h2>
        <p>Connecting homeowners with trusted home service professionals</p>
        
        <div class="features">
          <div class="feature">
            <i class="fas fa-search"></i>
            <span>Find trusted home service professionals</span>
          </div>
          <div class="feature">
            <i class="fas fa-calendar-check"></i>
            <span>Schedule services at your convenience</span>
          </div>
          <div class="feature">
            <i class="fas fa-shield-alt"></i>
            <span>Verified and background-checked professionals</span>
          </div>
          <div class="feature">
            <i class="fas fa-wallet"></i>
            <span>Secure payments and transparent pricing</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="./index.js"></script>
</body>
</html>