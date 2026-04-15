<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/DashboardController.php';
require_login();

$user = $_SESSION['user'];
$dashboardController = new DashboardController();
[
  'meals' => $meals,
  'requests' => $requests
] = $dashboardController->load($user);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Dashboard</title>
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
          <a class="nav-link active" href="dashboard.php">Dashboard</a>
          <a class="nav-link" href="meals.php">Meals</a>
          <a class="nav-link" href="exercises.php">Exercises</a>
          <a class="nav-link" href="store.php">Store</a>
          <a class="nav-link" href="profile.php">Profile</a>
          <a class="nav-link" href="support.php">Support</a>
          <?php if ($user['role'] === 'admin'): ?>
            <a class="nav-link" href="admin.php">Admin</a>
          <?php endif; ?>
        </nav>
      </aside>

      <section class="content">
        <header class="page-head">
          <div>
            <h2>Dashboard</h2>
            <p>Welcome back, <?= htmlspecialchars($user['name']) ?>.</p>
          </div>
          <a class="btn ghost" href="logout.php">Log out</a>
        </header>

        <div class="stats">
          <div class="stat">
            <p>Custom Meals</p>
            <h3><?= count($meals) ?></h3>
            <span>Total saved meals</span>
          </div>
          <div class="stat">
            <p>Support Requests</p>
            <h3><?= count($requests) ?></h3>
            <span>Requests submitted</span>
          </div>
          <div class="stat">
            <p>Role</p>
            <h3><?= htmlspecialchars($user['role']) ?></h3>
            <span>Access level</span>
          </div>
          <div class="stat">
            <p>Account Email</p>
            <h3><?= htmlspecialchars($user['email']) ?></h3>
            <span>Signed in</span>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
