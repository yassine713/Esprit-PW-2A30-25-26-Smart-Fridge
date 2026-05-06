<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/ProfilePageController.php';
require_login();

$user = $_SESSION['user'];
$profilePageController = new ProfilePageController();
['profile' => $profile] = $profilePageController->handle($user);
$profile = $profile ?: [];
$heightCm = (float) ($profile['height'] ?? 0);
$weightKg = (float) ($profile['weight'] ?? 0);
$heightM = $heightCm > 0 ? $heightCm / 100 : 0;
$bmi = ($heightM > 0 && $weightKg > 0) ? round($weightKg / ($heightM * $heightM), 1) : null;
$profileFields = ['height', 'weight', 'goal', 'budget', 'disease', 'allergy'];
$filledProfileFields = count(array_filter($profileFields, fn($field) => trim((string) ($profile[$field] ?? '')) !== ''));
$profileCompletion = (int) round(($filledProfileFields / count($profileFields)) * 100);
$profileQrText = implode("\n", [
  'NutriBudget Profile',
  'Name: ' . ($user['name'] ?? ''),
  'Email: ' . ($user['email'] ?? ''),
  'Height: ' . (($profile['height'] ?? '') !== '' ? ($profile['height'] . ' cm') : 'Not set'),
  'Weight: ' . (($profile['weight'] ?? '') !== '' ? ($profile['weight'] . ' kg') : 'Not set'),
  'Goal: ' . (($profile['goal'] ?? '') !== '' ? $profile['goal'] : 'Not set'),
  'Monthly Budget: ' . (($profile['budget'] ?? '') !== '' ? ('$' . $profile['budget']) : 'Not set'),
  'Health Conditions: ' . (($profile['disease'] ?? '') !== '' ? $profile['disease'] : 'None'),
  'Allergies: ' . (($profile['allergy'] ?? '') !== '' ? $profile['allergy'] : 'None')
]);
$profileQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($profileQrText);
$showProfileQr = $filledProfileFields > 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Profile</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    .profile-qr-card {
      align-items: center;
      display: grid;
      gap: 18px;
      grid-template-columns: auto 1fr;
    }

    .profile-qr-card img {
      background: #fff;
      border: 1px solid var(--user-line);
      border-radius: var(--user-radius);
      display: block;
      height: 180px;
      padding: 10px;
      width: 180px;
    }

    .profile-qr-card textarea {
      min-height: 132px;
      resize: vertical;
      width: 100%;
    }

    @media (max-width: 720px) {
      .profile-qr-card {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="app" data-view="dashboard" data-page="profile">
    <main class="dashboard">
      <aside class="sidebar">
        <a class="brand small" href="dashboard.php" aria-label="Go to dashboard">
          <div class="brand-mark"></div>
          <div>
            <h1>NutriBudget</h1>
            <p>Smart nutrition on a budget</p>
          </div>
        </a>
        <nav>
          <a class="nav-link" href="meals.php">Meals</a>
          <a class="nav-link" href="exercises.php">Exercises</a>
          <a class="nav-link" href="store.php">Store</a>
          <a class="nav-link active" href="profile.php">Profile</a>
          <a class="nav-link" href="support.php">Support</a>
          <?php if (($user['role'] ?? 'user') === 'admin'): ?>
            <a class="nav-link portal-link" href="access.php?target=admin"><span class="nav-icon">AP</span>Admin Panel</a>
          <?php endif; ?>
        </nav>
      </aside>

      <section class="content">
        <header class="page-head">
          <div class="page-head-copy">
            <span class="page-kicker">Body profile</span>
            <h2>Personalize every recommendation.</h2>
            <p>Keep your goals, budget, and health notes current so the app feels built around you.</p>
          </div>
          <div class="page-head-actions">
            <a class="btn ghost" href="support.php">Need help?</a>
            <a class="btn ghost" href="logout.php">Log out</a>
          </div>
        </header>

        <div class="insight-row profile-insights" aria-label="Profile overview">
          <div class="insight-card">
            <span>Profile ready</span>
            <strong><?= $profileCompletion ?>%</strong>
            <small><?= $filledProfileFields ?> of <?= count($profileFields) ?> fields filled</small>
          </div>
          <div class="insight-card">
            <span>BMI</span>
            <strong><?= $bmi !== null ? htmlspecialchars(number_format((float) $bmi, 1)) : '--' ?></strong>
            <small><?= $bmi !== null ? 'Based on height and weight' : 'Add height and weight' ?></small>
          </div>
          <div class="insight-card">
            <span>Budget</span>
            <strong><?= trim((string) ($profile['budget'] ?? '')) !== '' ? '$' . htmlspecialchars($profile['budget']) : '--' ?></strong>
            <small>Monthly food target</small>
          </div>
        </div>

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
        <?php if ($showProfileQr): ?>
          <div class="card profile-qr-card">
            <img src="<?= htmlspecialchars($profileQrUrl) ?>" alt="Profile QR code" />
            <div>
              <h3>Profile QR Code</h3>
              <p class="muted">Scan this code to read the saved profile summary.</p>
              <?php if (isset($_GET['saved'])): ?>
                <p class="muted">Profile saved. QR code updated.</p>
              <?php endif; ?>
              <textarea readonly><?= htmlspecialchars($profileQrText) ?></textarea>
            </div>
          </div>
        <?php endif; ?>
        <?php include __DIR__ . '/user_support_footer.php'; ?>
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
  <script src="user-panel.js"></script>
</body>
</html>
