<?php
require_once __DIR__ . '/../controller/SignupController.php';

$signupController = new SignupController();
['error' => $error] = $signupController->handle();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Sign up</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="app" data-view="signup">
    <aside class="auth-hero">
      <div class="brand">
        <div class="brand-mark"></div>
        <div>
          <h1>NutriBudget</h1>
          <p>Smart nutrition on a budget</p>
        </div>
      </div>

      <div class="card auth-card" data-screen="signup">
        <h2>Create your account</h2>
        <?php if ($error): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post" id="signup-form" novalidate>
          <label>
            <span>Full name</span>
            <input type="text" name="name" id="signup-name" placeholder="John Doe" />
            <small class="error" data-error-for="signup-name"></small>
          </label>
          <label>
            <span>Email address</span>
            <input type="text" name="email" id="signup-email" placeholder="you@example.com" />
            <small class="error" data-error-for="signup-email"></small>
          </label>
          <label>
            <span>Password</span>
            <input type="password" name="password" id="signup-password" placeholder="Create a password" />
            <small class="error" data-error-for="signup-password"></small>
          </label>
          <button class="btn primary" type="submit">Create account</button>
        </form>
        <p class="muted">Already have an account? <a class="link" href="login.php">Sign in</a></p>
      </div>
    </aside>

    <section class="auth-panel">
      <div class="panel-content" data-screen="signup">
        <div class="icon-badge">GO</div>
        <h2>Start your health journey</h2>
        <p>Join thousands of users achieving their fitness goals while staying within budget.</p>
      </div>
    </section>
  </div>

  <script>
    const form = document.getElementById('signup-form');
    const nameField = document.getElementById('signup-name');
    const emailField = document.getElementById('signup-email');
    const passField = document.getElementById('signup-password');

    function setError(id, message) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = message;
    }
    function clearError(id) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = '';
    }

    form.addEventListener('submit', (e) => {
      let ok = true;
      const nameOk = /^[A-Za-z\u00C0-\u00FF' -]{2,40}$/.test(nameField.value.trim());
      const emailOk = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(emailField.value.trim());
      const passOk = passField.value.trim().length >= 4;

      if (!nameOk) { setError('signup-name', 'Name must be letters only (min 2).'); ok = false; } else clearError('signup-name');
      if (!emailOk) { setError('signup-email', 'Enter a valid email.'); ok = false; } else clearError('signup-email');
      if (!passOk) { setError('signup-password', 'Password must be at least 4 characters.'); ok = false; } else clearError('signup-password');

      if (!ok) e.preventDefault();
    });
  </script>
</body>
</html>
