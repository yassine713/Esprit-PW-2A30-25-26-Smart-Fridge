<?php
require_once __DIR__ . '/../controller/LoginController.php';

$loginController = new LoginController();
['error' => $error] = $loginController->handle();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Sign in</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="app" data-view="login">
    <aside class="auth-hero">
      <div class="brand">
        <div class="brand-mark"></div>
        <div>
          <h1>NutriBudget</h1>
          <p>Smart nutrition on a budget</p>
        </div>
      </div>

      <div class="card auth-card" data-screen="login">
        <h2>Welcome back</h2>
        <?php if ($error): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post" id="login-form" novalidate>
          <label>
            <span>Email address</span>
            <input type="text" name="email" id="login-email" placeholder="you@example.com" />
            <small class="error" data-error-for="login-email"></small>
          </label>
          <label>
            <span>Password</span>
            <input type="password" name="password" id="login-password" placeholder="Enter your password" />
            <small class="error" data-error-for="login-password"></small>
          </label>
          <button class="btn primary" type="submit">Sign in</button>
        </form>
        <p class="muted">Don't have an account? <a class="link" href="signup.php">Sign up</a></p>
      </div>
    </aside>

    <section class="auth-panel">
      <div class="panel-content" data-screen="login">
        <div class="icon-badge">NB</div>
        <h2>Healthy eating made simple</h2>
        <p>Track your nutrition, manage your budget, and achieve your health goals with AI-powered insights.</p>
      </div>
    </section>
  </div>

  <script>
    const loginForm = document.getElementById('login-form');
    const loginEmail = document.getElementById('login-email');
    const loginPassword = document.getElementById('login-password');

    function setError(id, message) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = message;
    }

    function clearError(id) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = '';
    }

    loginForm.addEventListener('submit', (e) => {
      let ok = true;
      const email = loginEmail.value.trim();
      const pass = loginPassword.value.trim();
      const emailOk = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(email);

      if (!emailOk) { setError('login-email', 'Enter a valid email.'); ok = false; }
      else clearError('login-email');

      if (pass.length < 4) { setError('login-password', 'Password must be at least 4 characters.'); ok = false; }
      else clearError('login-password');

      if (!ok) e.preventDefault();
    });
  </script>
</body>
</html>
