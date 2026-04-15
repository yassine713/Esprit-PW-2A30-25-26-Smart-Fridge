<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/MealsPageController.php';
require_login();

$user = $_SESSION['user'];
$mealsPageController = new MealsPageController();
[
  'ingredients' => $ingredients,
  'meals' => $meals,
  'mealIngredientsMap' => $mealIngredientsMap
] = $mealsPageController->handle($user);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Meals</title>
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
          <a class="nav-link active" href="meals.php">Meals</a>
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
            <h2>Meals</h2>
            <p>Create and discover meals that fit your budget and goals</p>
          </div>
          <a class="btn ghost" href="logout.php">Log out</a>
        </header>

        <div class="two-col">
          <div class="card">
            <h3>Add Custom Meal</h3>
            <form method="post" id="meal-form" novalidate>
              <input type="hidden" name="action" value="add_meal" />
              <label>
                <span>Meal name</span>
                <input type="text" name="meal_name" id="meal-name" placeholder="Chicken breast with rice" />
                <small class="error" data-error-for="meal-name"></small>
              </label>
              <label>
                <span>Meal type</span>
                <input type="text" name="meal_type" id="meal-type" placeholder="Lunch" />
                <small class="error" data-error-for="meal-type"></small>
              </label>
              <label>
                <span>Ingredient (optional)</span>
                <select name="ingredient_id" id="meal-ingredient">
                  <option value="">Choose ingredient</option>
                  <?php foreach ($ingredients as $ing): ?>
                    <option value="<?= $ing['id'] ?>"><?= htmlspecialchars($ing['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                <span>Quantity (g)</span>
                <input type="text" name="quantity_g" id="meal-qty" placeholder="100" />
                <small class="error" data-error-for="meal-qty"></small>
              </label>
              <button class="btn primary" type="submit">Save Meal</button>
            </form>
          </div>

          <div class="card">
            <h3>My Meals</h3>
            <?php if (!$meals): ?>
              <p class="muted">No meals yet.</p>
            <?php else: ?>
              <div class="reclamations">
                <?php foreach ($meals as $meal): ?>
                  <div class="reclamation">
                    <div class="rec-main">
                      <h4><?= htmlspecialchars($meal['name']) ?></h4>
                      <p><?= htmlspecialchars($meal['type']) ?></p>
                      <div class="meta">
                        <span>Meal ID: <?= $meal['id'] ?></span>
                      </div>
                      <?php $ings = $mealIngredientsMap[$meal['id']] ?? []; ?>
                      <?php if ($ings): ?>
                        <ul class="mini-list">
                          <?php foreach ($ings as $mi): ?>
                            <li><?= htmlspecialchars($mi['name']) ?> - <?= htmlspecialchars($mi['quantity_g']) ?> g</li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>
                    </div>
                    <div class="actions">
                      <form method="post">
                        <input type="hidden" name="action" value="delete_meal" />
                        <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>" />
                        <button class="icon-btn danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    const form = document.getElementById('meal-form');
    const mealName = document.getElementById('meal-name');
    const mealType = document.getElementById('meal-type');
    const qty = document.getElementById('meal-qty');

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
      if (mealName.value.trim().length < 2) { setError('meal-name', 'Name must be at least 2 characters.'); ok = false; } else clearError('meal-name');
      if (mealType.value.trim().length < 2) { setError('meal-type', 'Type must be at least 2 characters.'); ok = false; } else clearError('meal-type');
      if (qty.value.trim() !== '' && !/^[0-9]+(\.[0-9]+)?$/.test(qty.value.trim())) { setError('meal-qty', 'Enter a valid number.'); ok = false; } else clearError('meal-qty');
      if (!ok) e.preventDefault();
    });
  </script>
</body>
</html>
