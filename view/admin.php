<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/AdminPageController.php';
require_admin();

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
] = $adminPageController->handle($user);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Admin</title>
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
          <a class="nav-link" href="profile.php">Profile</a>
          <a class="nav-link" href="support.php">Support</a>
          <a class="nav-link active" href="admin.php">Admin</a>
        </nav>
      </aside>

      <section class="content">
        <header class="page-head">
          <div>
            <h2>Admin Panel</h2>
            <p>Manage users, ingredients, exercises, products, categories, and support requests</p>
          </div>
          <a class="btn ghost" href="logout.php">Log out</a>
        </header>

        <div class="support-grid">
          <div class="card">
            <h3>Users</h3>
            <?php foreach ($users as $u): ?>
              <div class="reclamation">
                <div class="rec-main">
                  <h4><?= htmlspecialchars($u['name']) ?></h4>
                  <p><?= htmlspecialchars($u['email']) ?></p>
                  <div class="meta"><span>Role: <?= htmlspecialchars($u['role']) ?></span></div>
                </div>
                <div class="actions">
                  <form method="post">
                    <input type="hidden" name="action" value="set_role" />
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>" />
                    <select name="role">
                      <option <?= $u['role']==='user'?'selected':'' ?>>user</option>
                      <option <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
                    </select>
                    <button class="icon-btn" type="submit">Save</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="action" value="delete_user" />
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>" />
                    <button class="icon-btn danger" type="submit">Delete</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <h3>Categories</h3>
            <form method="post" class="inline-edit" novalidate>
              <input type="hidden" name="action" value="add_category" />
              <input type="text" name="c_name" placeholder="Category name" />
              <button class="icon-btn" type="submit">Add</button>
            </form>
            <?php foreach ($categories as $c): ?>
              <div class="reclamation">
                <div class="rec-main">
                  <form method="post" class="inline-edit" novalidate>
                    <input type="hidden" name="action" value="update_category" />
                    <input type="hidden" name="category_id" value="<?= $c['id'] ?>" />
                    <input type="text" name="c_name" value="<?= htmlspecialchars($c['name']) ?>" />
                    <button class="icon-btn" type="submit">Save</button>
                  </form>
                </div>
                <div class="actions">
                  <form method="post">
                    <input type="hidden" name="action" value="delete_category" />
                    <input type="hidden" name="category_id" value="<?= $c['id'] ?>" />
                    <button class="icon-btn danger" type="submit">Delete</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <h3>Products</h3>
            <form method="post" class="inline-edit" id="product-form" novalidate>
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
            <?php foreach ($products as $p): ?>
              <div class="reclamation">
                <div class="rec-main">
                  <form method="post" class="inline-edit" novalidate>
                    <input type="hidden" name="action" value="update_product" />
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>" />
                    <input type="text" name="p_name" value="<?= htmlspecialchars($p['name']) ?>" />
                    <input type="text" name="p_description" value="<?= htmlspecialchars($p['description']) ?>" />
                    <input type="text" name="p_price" value="<?= htmlspecialchars($p['price']) ?>" />
                    <input type="text" name="p_stock" value="<?= htmlspecialchars($p['stock']) ?>" />
                    <input type="text" name="p_image_url" value="<?= htmlspecialchars($p['image_url']) ?>" />
                    <button class="icon-btn" type="submit">Save</button>
                  </form>
                  <form method="post" class="inline-edit" novalidate>
                    <input type="hidden" name="action" value="set_product_categories" />
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>" />
                    <select name="category_ids[]" multiple>
                      <?php $pcatIds = $productCategoryIds[$p['id']] ?? []; ?>
                      <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= in_array($c['id'], $pcatIds) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="icon-btn" type="submit">Save Categories</button>
                  </form>
                </div>
                <div class="actions">
                  <form method="post">
                    <input type="hidden" name="action" value="delete_product" />
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>" />
                    <button class="icon-btn danger" type="submit">Delete</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <h3>Exercises</h3>
            <form method="post" class="inline-edit" novalidate>
              <input type="hidden" name="action" value="add_exercise" />
              <input type="text" name="name" placeholder="Exercise name" />
              <button class="icon-btn" type="submit">Add</button>
            </form>
            <?php foreach ($exercises as $ex): ?>
              <div class="reclamation">
                <div class="rec-main">
                  <form method="post" class="inline-edit" novalidate>
                    <input type="hidden" name="action" value="update_exercise" />
                    <input type="hidden" name="exercise_id" value="<?= $ex['id'] ?>" />
                    <input type="text" name="name" value="<?= htmlspecialchars($ex['name']) ?>" />
                    <button class="icon-btn" type="submit">Save</button>
                  </form>
                </div>
                <div class="actions">
                  <form method="post">
                    <input type="hidden" name="action" value="delete_exercise" />
                    <input type="hidden" name="exercise_id" value="<?= $ex['id'] ?>" />
                    <button class="icon-btn danger" type="submit">Delete</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <h3>Ingredients</h3>
            <form method="post" class="inline-edit" novalidate>
              <input type="hidden" name="action" value="add_ingredient" />
              <input type="text" name="name" placeholder="Name" />
              <input type="text" name="calories" placeholder="Calories" />
              <input type="text" name="protein" placeholder="Protein" />
              <input type="text" name="carbs" placeholder="Carbs" />
              <input type="text" name="fat" placeholder="Fat" />
              <input type="text" name="price" placeholder="Price" />
              <button class="icon-btn" type="submit">Add</button>
            </form>
            <?php foreach ($ingredients as $ing): ?>
              <div class="reclamation">
                <div class="rec-main">
                  <form method="post" class="inline-edit" novalidate>
                    <input type="hidden" name="action" value="update_ingredient" />
                    <input type="hidden" name="ingredient_id" value="<?= $ing['id'] ?>" />
                    <input type="text" name="name" value="<?= htmlspecialchars($ing['name']) ?>" />
                    <input type="text" name="calories" value="<?= htmlspecialchars($ing['calories']) ?>" />
                    <input type="text" name="protein" value="<?= htmlspecialchars($ing['protein']) ?>" />
                    <input type="text" name="carbs" value="<?= htmlspecialchars($ing['carbs']) ?>" />
                    <input type="text" name="fat" value="<?= htmlspecialchars($ing['fat']) ?>" />
                    <input type="text" name="price" value="<?= htmlspecialchars($ing['price']) ?>" />
                    <button class="icon-btn" type="submit">Save</button>
                  </form>
                </div>
                <div class="actions">
                  <form method="post">
                    <input type="hidden" name="action" value="delete_ingredient" />
                    <input type="hidden" name="ingredient_id" value="<?= $ing['id'] ?>" />
                    <button class="icon-btn danger" type="submit">Delete</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <h3>Support Requests</h3>
            <?php foreach ($requests as $r): ?>
              <div class="reclamation">
                <div class="rec-main">
                  <h4><?= htmlspecialchars($r['issue_title']) ?></h4>
                  <p><?= htmlspecialchars($r['description']) ?></p>
                  <div class="meta">
                    <span><?= htmlspecialchars($r['email']) ?></span>
                    <span class="status <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span>
                  </div>
                  <form method="post" class="inline-edit" novalidate>
                    <input type="hidden" name="action" value="add_response" />
                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>" />
                    <input type="text" name="message" placeholder="Response message" />
                    <button class="icon-btn" type="submit">Respond</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
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
  </script>
</body>
</html>
