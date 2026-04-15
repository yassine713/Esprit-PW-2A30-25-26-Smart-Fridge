<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/StorePageController.php';
require_login();

$user = $_SESSION['user'];
$storePageController = new StorePageController();
['products' => $products] = $storePageController->load();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Store</title>
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
          <a class="nav-link active" href="store.php">Store</a>
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
            <h2>Store</h2>
            <p>See what's available today</p>
          </div>
          <a class="btn ghost" href="logout.php">Log out</a>
        </header>

        <div class="card">
          <button class="btn primary" id="show-products" type="button">See what's available today</button>
          <div id="product-list" class="product-grid" hidden>
            <?php if (!$products): ?>
              <p class="muted">No products available today.</p>
            <?php else: ?>
              <?php foreach ($products as $p): ?>
                <div class="product-card">
                  <div class="product-thumb">
                    <?php if (!empty($p['image_url'])): ?>
                      <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" />
                    <?php else: ?>
                      <div class="product-placeholder">IMG</div>
                    <?php endif; ?>
                  </div>
                  <div>
                    <h4><?= htmlspecialchars($p['name']) ?></h4>
                    <p><?= htmlspecialchars($p['description']) ?></p>
                    <div class="meta">
                      <span>$<?= htmlspecialchars($p['price']) ?></span>
                      <span>Stock: <?= htmlspecialchars($p['stock']) ?></span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    const btn = document.getElementById('show-products');
    const list = document.getElementById('product-list');
    btn.addEventListener('click', () => {
      list.hidden = !list.hidden;
      btn.textContent = list.hidden ? "See what's available today" : 'Hide products';
    });
  </script>
</body>
</html>
