<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/ProfilePageController.php';
require_login();

$user = $_SESSION['user'];
$profilePageController = new ProfilePageController();
['profile' => $profile] = $profilePageController->handle($user);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Profile</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="app" data-view="dashboard">
    <main class="dashboard">
      <aside class="sidebar">
        <div class="brand small">
          <div class="brand-mark"></div>
          <div>
            <h1>NutriBudget</h1>
            <p>Smart nutrition on a budget</p>
          </div>
        </div>
        <nav>
          <a class="nav-link" href="dashboard.php">Dashboard</a>
          <a class="nav-link" href="meals.php">Meals</a>
          <a class="nav-link" href="exercises.php">Exercises</a>
          <a class="nav-link" href="store.php">Store</a>
          <a class="nav-link active" href="profile.php">Profile</a>
          <a class="nav-link" href="support.php">Support</a>
          <?php if ($user['role'] === 'admin'): ?>
            <a class="nav-link" href="admin.php">Admin</a>
          <?php endif; ?>
        </nav>
      </aside>

      <section class="content">
        <header class="page-head">
          <div>
            <h2>Profile Settings</h2>
            <p>Manage your personal information and preferences</p>
          </div>
          <a class="btn ghost" href="logout.php">Log out</a>
        </header>

        <form method="post" id="profile-form" class="stack" novalidate>
          <div class="card profile-card">
            <div class="avatar"><?= strtoupper(substr($user['name'], 0, 2)) ?></div>
            <div>
              <h3><?= htmlspecialchars($user['name']) ?></h3>
              <p class="muted"><?= htmlspecialchars($user['email']) ?></p>
            </div>
          </div>

          <div class="card">
            <h3>Physical Information</h3>
            <div class="two-col inputs">
              <label>
                <span>Height (cm)</span>
                <input type="text" name="height" id="profile-height" value="<?= htmlspecialchars($profile['height'] ?? '') ?>" />
                <small class="error" data-error-for="profile-height"></small>
              </label>
              <label>
                <span>Weight (kg)</span>
                <input type="text" name="weight" id="profile-weight" value="<?= htmlspecialchars($profile['weight'] ?? '') ?>" />
                <small class="error" data-error-for="profile-weight"></small>
              </label>
            </div>
          </div>

          <div class="card">
            <h3>Goals & Preferences</h3>
            <label>
              <span>Fitness goal</span>
              <input type="text" name="goal" id="profile-goal" value="<?= htmlspecialchars($profile['goal'] ?? '') ?>" />
              <small class="error" data-error-for="profile-goal"></small>
            </label>
            <label>
              <span>Monthly Food Budget (USD)</span>
              <input type="text" name="budget" id="profile-budget" value="<?= htmlspecialchars($profile['budget'] ?? '') ?>" />
              <small class="error" data-error-for="profile-budget"></small>
            </label>
          </div>

          <div class="card">
            <h3>Health Information</h3>
            <label>
              <span>Health conditions</span>
              <input type="text" name="disease" id="profile-disease" value="<?= htmlspecialchars($profile['disease'] ?? '') ?>" />
              <small class="error" data-error-for="profile-disease"></small>
            </label>
            <label>
              <span>Allergies</span>
              <input type="text" name="allergy" id="profile-allergy" value="<?= htmlspecialchars($profile['allergy'] ?? '') ?>" />
              <small class="error" data-error-for="profile-allergy"></small>
            </label>
          </div>

          <button class="btn primary" type="submit">Save Changes</button>
        </form>
      </section>
    </main>
  </div>

  <script>
    const form = document.getElementById('profile-form');
    const height = document.getElementById('profile-height');
    const weight = document.getElementById('profile-weight');
    const budget = document.getElementById('profile-budget');

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
      const num = (v) => v === '' || /^[0-9]+(\.[0-9]+)?$/.test(v);

      if (!num(height.value.trim())) { setError('profile-height', 'Enter a valid number.'); ok = false; } else clearError('profile-height');
      if (!num(weight.value.trim())) { setError('profile-weight', 'Enter a valid number.'); ok = false; } else clearError('profile-weight');
      if (!num(budget.value.trim())) { setError('profile-budget', 'Enter a valid number.'); ok = false; } else clearError('profile-budget');

      if (!ok) e.preventDefault();
    });
  </script>
</body>
</html>
