<?php
require_once __DIR__ . '/../../controller/auth.php';
require_once __DIR__ . '/../../controller/AdminPageController.php';
require_admin('../login.php', '../dashboard.php');

$user = $_SESSION['user'];
$adminPageController = new AdminPageController();
[
  'users' => $users,
  'ingredients' => $ingredients,
  'requests' => $requests,
  'exercises' => $exercises,
  'products' => $products,
  'categories' => $categories,
  'productCategoryIds' => $productCategoryIds
] = $adminPageController->handle($user, 'index.php');

if (!function_exists('e')) {
  function e($value)
  {
    return htmlspecialchars((string) $value);
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Admin</title>
  <link rel="stylesheet" href="../styles.css" />
</head>
<body>
  <div class="app" data-view="admin">
    <main class="admin-layout">
      <aside class="admin-sidebar">
        <div class="admin-side-shell">
          <div class="brand small">
            <div class="brand-mark"></div>
            <div>
              <h1>NutriBudget</h1>
              <p>Admin website</p>
            </div>
          </div>

          <div class="admin-profile-card">
            <span>Signed in as</span>
            <strong><?= e($user['name']) ?></strong>
            <p>Food and fitness operations</p>
          </div>

          <nav class="admin-nav" aria-label="Admin sections">
            <a class="nav-link active" href="#users">
              <span class="nav-chip">01</span>
              <span class="nav-copy"><strong>Users</strong><small>Roles & access</small></span>
              <span class="nav-count"><?= count($users) ?></span>
            </a>
            <a class="nav-link" href="#categories">
              <span class="nav-chip">02</span>
              <span class="nav-copy"><strong>Categories</strong><small>Food groups</small></span>
              <span class="nav-count"><?= count($categories) ?></span>
            </a>
            <a class="nav-link" href="#products">
              <span class="nav-chip">03</span>
              <span class="nav-copy"><strong>Products</strong><small>Store stock</small></span>
              <span class="nav-count"><?= count($products) ?></span>
            </a>
            <a class="nav-link" href="#exercises">
              <span class="nav-chip">04</span>
              <span class="nav-copy"><strong>Exercises</strong><small>Movement library</small></span>
              <span class="nav-count"><?= count($exercises) ?></span>
            </a>
            <a class="nav-link" href="#ingredients">
              <span class="nav-chip">05</span>
              <span class="nav-copy"><strong>Ingredients</strong><small>Nutrition base</small></span>
              <span class="nav-count"><?= count($ingredients) ?></span>
            </a>
            <a class="nav-link" href="#support">
              <span class="nav-chip">06</span>
              <span class="nav-copy"><strong>Support</strong><small>User requests</small></span>
              <span class="nav-count"><?= count($requests) ?></span>
            </a>
            <a class="nav-link admin-portal-link" href="../access.php?target=user">
              <span class="nav-chip">US</span>
              <span class="nav-copy"><strong>User Site</strong><small>Member website</small></span>
              <span class="nav-count">Go</span>
            </a>
          </nav>
        </div>
      </aside>

      <section class="admin-content">
        <header class="page-head admin-head">
          <div>
            <p class="admin-kicker">Food fitness command center</p>
            <h2>Admin Panel</h2>
            <p>Manage users, ingredients, exercises, products, categories, and support requests.</p>
          </div>
          <a class="btn ghost" href="../logout.php">Log out</a>
        </header>
        <?php if ($notice = access_notice()): ?>
          <div class="access-alert"><?= e($notice) ?></div>
        <?php endif; ?>

        <div class="admin-signal-row">
          <a class="admin-signal" href="#products">
            <span>Store</span>
            <strong><?= count($products) ?></strong>
          </a>
          <a class="admin-signal" href="#ingredients">
            <span>Nutrition</span>
            <strong><?= count($ingredients) ?></strong>
          </a>
          <a class="admin-signal" href="#exercises">
            <span>Fitness</span>
            <strong><?= count($exercises) ?></strong>
          </a>
          <a class="admin-signal" href="#support">
            <span>Support</span>
            <strong><?= count($requests) ?></strong>
          </a>
        </div>

        <div class="admin-grid">
          <section class="admin-panel" id="users">
            <div class="admin-panel-head">
              <div>
                <p>Access</p>
                <h3>Users</h3>
              </div>
              <span><?= count($users) ?></span>
            </div>
            <div class="admin-list">
              <?php foreach ($users as $u): ?>
                <article class="admin-item">
                  <div class="admin-item-main">
                    <h4><?= e($u['name']) ?></h4>
                    <p><?= e($u['email']) ?></p>
                    <div class="meta"><span>Role: <?= e($u['role']) ?></span></div>
                  </div>
                  <div class="admin-actions">
                    <form method="post" class="admin-inline">
                      <input type="hidden" name="action" value="set_role" />
                      <input type="hidden" name="user_id" value="<?= e($u['id']) ?>" />
                      <select name="role">
                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option>
                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                      </select>
                      <button class="icon-btn" type="submit">Save</button>
                    </form>
                    <form method="post">
                      <input type="hidden" name="action" value="delete_user" />
                      <input type="hidden" name="user_id" value="<?= e($u['id']) ?>" />
                      <button class="icon-btn danger" type="submit">Delete</button>
                    </form>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="admin-panel" id="categories">
            <div class="admin-panel-head">
              <div>
                <p>Catalog</p>
                <h3>Categories</h3>
              </div>
              <span><?= count($categories) ?></span>
            </div>
            <form method="post" class="admin-form compact" novalidate>
              <input type="hidden" name="action" value="add_category" />
              <input type="text" name="c_name" placeholder="Category name" />
              <button class="icon-btn" type="submit">Add</button>
            </form>
            <div class="admin-list">
              <?php foreach ($categories as $c): ?>
                <article class="admin-item">
                  <form method="post" class="admin-form compact" novalidate>
                    <input type="hidden" name="action" value="update_category" />
                    <input type="hidden" name="category_id" value="<?= e($c['id']) ?>" />
                    <input type="text" name="c_name" value="<?= e($c['name']) ?>" />
                    <button class="icon-btn" type="submit">Save</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="action" value="delete_category" />
                    <input type="hidden" name="category_id" value="<?= e($c['id']) ?>" />
                    <button class="icon-btn danger" type="submit">Delete</button>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="admin-panel wide" id="products">
            <div class="admin-panel-head">
              <div>
                <p>Store</p>
                <h3>Products</h3>
              </div>
              <span><?= count($products) ?></span>
            </div>
            <form method="post" class="admin-form grid-form" id="product-form" novalidate>
              <input type="hidden" name="action" value="add_product" />
              <input type="text" name="p_name" id="p-name" placeholder="Name" />
              <input type="text" name="p_description" id="p-description" placeholder="Description" />
              <input type="text" name="p_price" id="p-price" placeholder="Price" />
              <input type="text" name="p_stock" id="p-stock" placeholder="Stock" />
              <input type="text" name="p_image_url" id="p-image" placeholder="Image URL (optional)" />
              <button class="icon-btn" type="submit">Add</button>
              <small class="error" data-error-for="p-name"></small>
              <small class="error" data-error-for="p-description"></small>
              <small class="error" data-error-for="p-price"></small>
              <small class="error" data-error-for="p-stock"></small>
            </form>
            <div class="admin-list">
              <?php foreach ($products as $p): ?>
                <article class="admin-item product-admin-item">
                  <div class="admin-item-main">
                    <form method="post" class="admin-form grid-form" novalidate>
                      <input type="hidden" name="action" value="update_product" />
                      <input type="hidden" name="product_id" value="<?= e($p['id']) ?>" />
                      <input type="text" name="p_name" value="<?= e($p['name']) ?>" />
                      <input type="text" name="p_description" value="<?= e($p['description']) ?>" />
                      <input type="text" name="p_price" value="<?= e($p['price']) ?>" />
                      <input type="text" name="p_stock" value="<?= e($p['stock']) ?>" />
                      <input type="text" name="p_image_url" value="<?= e($p['image_url']) ?>" />
                      <button class="icon-btn" type="submit">Save</button>
                    </form>
                    <form method="post" class="admin-form compact" novalidate>
                      <input type="hidden" name="action" value="set_product_categories" />
                      <input type="hidden" name="product_id" value="<?= e($p['id']) ?>" />
                      <select name="category_ids[]" multiple>
                        <?php $pcatIds = $productCategoryIds[$p['id']] ?? []; ?>
                        <?php foreach ($categories as $c): ?>
                          <option value="<?= e($c['id']) ?>" <?= in_array($c['id'], $pcatIds) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button class="icon-btn" type="submit">Save Categories</button>
                    </form>
                  </div>
                  <form method="post">
                    <input type="hidden" name="action" value="delete_product" />
                    <input type="hidden" name="product_id" value="<?= e($p['id']) ?>" />
                    <button class="icon-btn danger" type="submit">Delete</button>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="admin-panel" id="exercises">
            <div class="admin-panel-head">
              <div>
                <p>Fitness</p>
                <h3>Exercises</h3>
              </div>
              <span><?= count($exercises) ?></span>
            </div>
            <form method="post" class="admin-form compact" novalidate>
              <input type="hidden" name="action" value="add_exercise" />
              <input type="text" name="name" placeholder="Exercise name" />
              <button class="icon-btn" type="submit">Add</button>
            </form>
            <div class="admin-list">
              <?php foreach ($exercises as $ex): ?>
                <article class="admin-item">
                  <form method="post" class="admin-form compact" novalidate>
                    <input type="hidden" name="action" value="update_exercise" />
                    <input type="hidden" name="exercise_id" value="<?= e($ex['id']) ?>" />
                    <input type="text" name="name" value="<?= e($ex['name']) ?>" />
                    <button class="icon-btn" type="submit">Save</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="action" value="delete_exercise" />
                    <input type="hidden" name="exercise_id" value="<?= e($ex['id']) ?>" />
                    <button class="icon-btn danger" type="submit">Delete</button>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="admin-panel wide" id="ingredients">
            <div class="admin-panel-head">
              <div>
                <p>Meals</p>
                <h3>Ingredients</h3>
              </div>
              <span><?= count($ingredients) ?></span>
            </div>
            <form method="post" class="admin-form grid-form" id="ingredient-form" novalidate>
              <input type="hidden" name="action" value="add_ingredient" />
              <input type="text" name="name" id="ing-name" placeholder="Name" required minlength="2" maxlength="80" />
              <input type="number" name="calories" id="ing-calories" placeholder="Calories" required min="0" max="5000" step="1" />
              <input type="number" name="protein" id="ing-protein" placeholder="Protein" required min="0" max="1000" step="0.01" />
              <input type="number" name="carbs" id="ing-carbs" placeholder="Carbs" required min="0" max="1000" step="0.01" />
              <input type="number" name="fat" id="ing-fat" placeholder="Fat" required min="0" max="1000" step="0.01" />
              <input type="number" name="price" id="ing-price" placeholder="Price" required min="0" max="999999" step="0.01" />
              <button class="icon-btn" type="submit">Add</button>
              <small class="error" data-error-for="ing-name"></small>
              <small class="error" data-error-for="ing-calories"></small>
              <small class="error" data-error-for="ing-protein"></small>
              <small class="error" data-error-for="ing-carbs"></small>
              <small class="error" data-error-for="ing-fat"></small>
              <small class="error" data-error-for="ing-price"></small>
            </form>
            <div class="admin-list">
              <?php foreach ($ingredients as $ing): ?>
                <article class="admin-item">
                  <form method="post" class="admin-form grid-form ingredient-edit-form">
                    <input type="hidden" name="action" value="update_ingredient" />
                    <input type="hidden" name="ingredient_id" value="<?= e($ing['id']) ?>" />
                    <input type="text" name="name" value="<?= e($ing['name']) ?>" required minlength="2" maxlength="80" />
                    <input type="number" name="calories" value="<?= e($ing['calories']) ?>" required min="0" max="5000" step="1" />
                    <input type="number" name="protein" value="<?= e($ing['protein']) ?>" required min="0" max="1000" step="0.01" />
                    <input type="number" name="carbs" value="<?= e($ing['carbs']) ?>" required min="0" max="1000" step="0.01" />
                    <input type="number" name="fat" value="<?= e($ing['fat']) ?>" required min="0" max="1000" step="0.01" />
                    <input type="number" name="price" value="<?= e($ing['price']) ?>" required min="0" max="999999" step="0.01" />
                    <button class="icon-btn" type="submit">Save</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="action" value="delete_ingredient" />
                    <input type="hidden" name="ingredient_id" value="<?= e($ing['id']) ?>" />
                    <button class="icon-btn danger" type="submit">Delete</button>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="admin-panel wide" id="support">
            <div class="admin-panel-head">
              <div>
                <p>Help Desk</p>
                <h3>Support Requests</h3>
              </div>
              <span><?= count($requests) ?></span>
            </div>
            <div class="admin-list">
              <?php foreach ($requests as $r): ?>
                <article class="admin-item support-admin-item">
                  <div class="admin-item-main">
                    <h4><?= e($r['issue_title']) ?></h4>
                    <p><?= e($r['description']) ?></p>
                    <div class="meta">
                      <span><?= e($r['email']) ?></span>
                      <span class="status <?= e($r['status']) ?>"><?= e($r['status']) ?></span>
                    </div>
                    <form method="post" class="admin-form compact" novalidate>
                      <input type="hidden" name="action" value="add_response" />
                      <input type="hidden" name="request_id" value="<?= e($r['id']) ?>" />
                      <input type="text" name="message" placeholder="Response message" />
                      <button class="icon-btn" type="submit">Respond</button>
                    </form>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        </div>
      </section>
    </main>
  </div>

  <script>
    const form = document.getElementById('product-form');
    const nameField = document.getElementById('p-name');
    const descField = document.getElementById('p-description');
    const priceField = document.getElementById('p-price');
    const stockField = document.getElementById('p-stock');
    const ingredientForm = document.getElementById('ingredient-form');
    const ingredientName = document.getElementById('ing-name');
    const ingredientCalories = document.getElementById('ing-calories');
    const ingredientProtein = document.getElementById('ing-protein');
    const ingredientCarbs = document.getElementById('ing-carbs');
    const ingredientFat = document.getElementById('ing-fat');
    const ingredientPrice = document.getElementById('ing-price');

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
      if (nameField.value.trim().length < 2) { setError('p-name', 'Name must be at least 2 characters.'); ok = false; } else clearError('p-name');
      if (descField.value.trim().length < 4) { setError('p-description', 'Description must be at least 4 characters.'); ok = false; } else clearError('p-description');
      if (!/^[0-9]+(\.[0-9]+)?$/.test(priceField.value.trim())) { setError('p-price', 'Enter a valid price.'); ok = false; } else clearError('p-price');
      if (!/^[0-9]+$/.test(stockField.value.trim())) { setError('p-stock', 'Enter a valid stock number.'); ok = false; } else clearError('p-stock');
      if (!ok) e.preventDefault();
    });

    function isPositiveNumber(field, max) {
      const value = field.value.trim();
      return value !== '' && /^[0-9]+(\.[0-9]+)?$/.test(value) && Number(value) >= 0 && Number(value) <= max;
    }

    ingredientForm.addEventListener('submit', (e) => {
      let ok = true;
      if (ingredientName.value.trim().length < 2) { setError('ing-name', 'Name must be at least 2 characters.'); ok = false; } else clearError('ing-name');
      if (!isPositiveNumber(ingredientCalories, 5000) || !Number.isInteger(Number(ingredientCalories.value))) { setError('ing-calories', 'Calories must be a whole number from 0 to 5000.'); ok = false; } else clearError('ing-calories');
      if (!isPositiveNumber(ingredientProtein, 1000)) { setError('ing-protein', 'Protein must be a number from 0 to 1000.'); ok = false; } else clearError('ing-protein');
      if (!isPositiveNumber(ingredientCarbs, 1000)) { setError('ing-carbs', 'Carbs must be a number from 0 to 1000.'); ok = false; } else clearError('ing-carbs');
      if (!isPositiveNumber(ingredientFat, 1000)) { setError('ing-fat', 'Fat must be a number from 0 to 1000.'); ok = false; } else clearError('ing-fat');
      if (!isPositiveNumber(ingredientPrice, 999999)) { setError('ing-price', 'Price must be a valid positive number.'); ok = false; } else clearError('ing-price');
      if (!ok) e.preventDefault();
    });

    const nav = document.querySelector('.admin-nav');
    const navLinks = Array.from(document.querySelectorAll('.admin-nav .nav-link'));
    const jumpLinks = Array.from(document.querySelectorAll('.admin-nav .nav-link, .admin-signal'));
    const panels = Array.from(document.querySelectorAll('.admin-panel[id]'));

    function setActivePanel(id, pulse = false) {
      navLinks.forEach((link) => link.classList.toggle('active', link.getAttribute('href') === `#${id}`));
      const activeLink = navLinks.find((link) => link.getAttribute('href') === `#${id}`);

      if (activeLink && nav) {
        nav.style.setProperty('--active-top', `${activeLink.offsetTop}px`);
        nav.style.setProperty('--active-height', `${activeLink.offsetHeight}px`);
      }

      if (pulse) {
        const panel = document.getElementById(id);
        if (panel) {
          panel.classList.remove('is-current');
          window.requestAnimationFrame(() => panel.classList.add('is-current'));
          window.setTimeout(() => panel.classList.remove('is-current'), 900);
        }
      }
    }

    jumpLinks.forEach((link) => {
      link.addEventListener('click', (event) => {
        const id = link.getAttribute('href').slice(1);
        const panel = document.getElementById(id);
        if (!panel) return;

        event.preventDefault();
        setActivePanel(id, true);
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        history.replaceState(null, '', `#${id}`);
      });
    });

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) setActivePanel(entry.target.id);
      });
    }, { rootMargin: '-28% 0px -58% 0px', threshold: 0.1 });

    panels.forEach((panel) => observer.observe(panel));
    setActivePanel(location.hash ? location.hash.slice(1) : 'users');
    window.addEventListener('resize', () => {
      const active = navLinks.find((link) => link.classList.contains('active'));
      if (active) setActivePanel(active.getAttribute('href').slice(1));
    });
  </script>
</body>
</html>
