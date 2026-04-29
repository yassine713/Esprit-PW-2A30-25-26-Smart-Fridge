<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/MealsPageController.php';
require_login();

$user = $_SESSION['user'];
$mealsPageController = new MealsPageController();
[
  'ingredients' => $ingredients,
  'meals' => $meals,
  'mealIngredientsMap' => $mealIngredientsMap,
  'mealCoachMap' => $mealCoachMap
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
          <a class="nav-link portal-link" href="access.php?target=admin"><span class="nav-icon">AP</span>Admin Panel</a>
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
              <div class="meal-builder">
                <span>Ingredients (optional)</span>
                <div id="meal-ingredients-list" class="meal-builder-list">
                  <div class="meal-ingredient-grid meal-builder-row">
                    <select name="ingredient_id[]" id="meal-ingredient">
                      <option value="">Choose ingredient</option>
                      <?php foreach ($ingredients as $ing): ?>
                        <option value="<?= $ing['id'] ?>"><?= htmlspecialchars($ing['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <input type="text" name="quantity_g[]" id="meal-qty" placeholder="g" />
                    <button class="icon-btn danger remove-ingredient" type="button" hidden>Remove</button>
                  </div>
                </div>
                <button class="btn soft tiny" type="button" id="add-ingredient-row">Add ingredient</button>
                <small class="error" data-error-for="meal-qty"></small>
              </div>
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
                  <?php $coach = $mealCoachMap[$meal['id']] ?? null; ?>
                  <div class="reclamation">
                    <div class="rec-main">
                      <form method="post" class="inline-edit meal-edit-form" novalidate>
                        <input type="hidden" name="action" value="update_meal" />
                        <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>" />
                        <label>
                          <span>Meal name</span>
                          <input type="text" name="meal_name" value="<?= htmlspecialchars($meal['name']) ?>" />
                        </label>
                        <label>
                          <span>Meal type</span>
                          <input type="text" name="meal_type" value="<?= htmlspecialchars($meal['type']) ?>" />
                        </label>
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
                        <div class="actions">
                          <button class="icon-btn" type="submit">Save</button>
                        </div>
                      </form>
                      <form method="post" class="meal-ingredient-form" novalidate>
                        <input type="hidden" name="action" value="add_meal_ingredient" />
                        <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>" />
                        <div>
                          <span>Add ingredient</span>
                          <div class="meal-ingredient-grid">
                            <select name="ingredient_id">
                              <option value="">Choose ingredient</option>
                              <?php foreach ($ingredients as $ing): ?>
                                <option value="<?= $ing['id'] ?>"><?= htmlspecialchars($ing['name']) ?></option>
                              <?php endforeach; ?>
                            </select>
                            <input type="text" name="quantity_g" placeholder="g" />
                            <button class="icon-btn" type="submit">Add</button>
                          </div>
                        </div>
                      </form>
                      <?php if ($coach): ?>
                        <div class="meal-coach">
                          <div class="meal-coach-copy">
                            <div class="meal-coach-kicker">
                              <span>Smart Meal Coach</span>
                              <strong><?= htmlspecialchars($coach['badge']) ?></strong>
                            </div>
                            <h4>Cook <?= htmlspecialchars($meal['name']) ?></h4>
                            <p><?= htmlspecialchars($coach['tip']) ?></p>
                          </div>
                          <?php if ($coach['video']): ?>
                            <div class="meal-video">
                              <iframe
                                src="https://www.youtube.com/embed/<?= htmlspecialchars($coach['video']['id']) ?>"
                                title="<?= htmlspecialchars($coach['video']['title']) ?>"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                            </div>
                            <p class="meal-video-title">
                              <?= htmlspecialchars($coach['video']['title']) ?> &middot; <?= htmlspecialchars($coach['video']['channel']) ?>
                            </p>
                          <?php else: ?>
                            <div class="meal-video-placeholder">
                              <div class="play-mark">TV</div>
                              <div>
                                <strong>Cooking video frame</strong>
                                <p>
                                  <?php if ($coach['hasApiKey']): ?>
                                    No embeddable video was found yet. Try adding more precise ingredients.
                                  <?php else: ?>
                                    Add your YouTube API key in config.php to load the tutorial here.
                                  <?php endif; ?>
                                </p>
                                <small><?= htmlspecialchars($coach['query']) ?></small>
                              </div>
                            </div>
                          <?php endif; ?>
                        </div>
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
    const ingredientsList = document.getElementById('meal-ingredients-list');
    const addIngredientRow = document.getElementById('add-ingredient-row');

    function setError(id, message) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = message;
    }
    function clearError(id) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = '';
    }

    function refreshRemoveButtons() {
      const rows = ingredientsList.querySelectorAll('.meal-builder-row');
      rows.forEach((row) => {
        const removeButton = row.querySelector('.remove-ingredient');
        removeButton.hidden = rows.length === 1;
      });
    }

    addIngredientRow.addEventListener('click', () => {
      const firstRow = ingredientsList.querySelector('.meal-builder-row');
      const row = firstRow.cloneNode(true);
      row.querySelector('select').value = '';
      row.querySelector('input').value = '';
      row.querySelector('select').removeAttribute('id');
      row.querySelector('input').removeAttribute('id');
      ingredientsList.appendChild(row);
      refreshRemoveButtons();
    });

    ingredientsList.addEventListener('click', (e) => {
      if (!e.target.classList.contains('remove-ingredient')) return;
      e.target.closest('.meal-builder-row').remove();
      refreshRemoveButtons();
    });

    form.addEventListener('submit', (e) => {
      let ok = true;
      const rows = ingredientsList.querySelectorAll('.meal-builder-row');
      if (mealName.value.trim().length < 2) { setError('meal-name', 'Name must be at least 2 characters.'); ok = false; } else clearError('meal-name');
      if (mealType.value.trim().length < 2) { setError('meal-type', 'Type must be at least 2 characters.'); ok = false; } else clearError('meal-type');
      clearError('meal-qty');
      rows.forEach((row) => {
        const ingredient = row.querySelector('select');
        const quantity = row.querySelector('input');
        const hasIngredient = ingredient.value !== '';
        const hasQuantity = quantity.value.trim() !== '';

        if ((hasIngredient || hasQuantity) && (ingredient.value === '' || !/^[0-9]+(\.[0-9]+)?$/.test(quantity.value.trim()) || Number(quantity.value) <= 0)) {
          setError('meal-qty', 'Choose each ingredient and enter a valid quantity.');
          ok = false;
        }
      });
      if (!ok) e.preventDefault();
    });

    refreshRemoveButtons();

    document.querySelectorAll('.meal-edit-form').forEach((editForm) => {
      editForm.addEventListener('submit', (e) => {
        const name = editForm.querySelector('[name="meal_name"]');
        const type = editForm.querySelector('[name="meal_type"]');

        if (name.value.trim().length < 2 || type.value.trim().length < 2) {
          e.preventDefault();
          alert('Meal name and type must be at least 2 characters.');
        }
      });
    });

    document.querySelectorAll('.meal-ingredient-form').forEach((ingredientForm) => {
      ingredientForm.addEventListener('submit', (e) => {
        const ingredient = ingredientForm.querySelector('[name="ingredient_id"]');
        const quantity = ingredientForm.querySelector('[name="quantity_g"]');

        if (ingredient.value === '' || !/^[0-9]+(\.[0-9]+)?$/.test(quantity.value.trim())) {
          e.preventDefault();
          alert('Choose an ingredient and enter a valid quantity.');
        }
      });
    });
  </script>
</body>
</html>
