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
  'mealCoachMap' => $mealCoachMap,
  'mealProteinMap' => $mealProteinMap,
  'sort' => $sort,
  'dir' => $dir,
  'profile' => $profile,
  'profileBudget' => $profileBudget,
  'aiMealSuggestions' => $aiMealSuggestions,
  'aiMealError' => $aiMealError,
  'aiForm' => $aiForm
] = $mealsPageController->handle($user);

$isProteinSort = ($sort ?? '') === 'protein';
$organizeHref = $isProteinSort ? 'meals.php' : 'meals.php?sort=protein&dir=desc';
$organizeLabel = $isProteinSort ? 'Clear' : 'Organize';
$totalProteinG = array_sum($mealProteinMap);
$averageProteinG = count($meals) > 0 ? $totalProteinG / count($meals) : 0;

if (!function_exists('e')) {
  function e($value)
  {
    return htmlspecialchars((string) $value);
  }
}

function meal_macro_summary($ingredients)
{
  $summary = [
    'calories' => 0.0,
    'protein' => 0.0,
    'carbs' => 0.0,
    'fat' => 0.0
  ];

  foreach ($ingredients as $ingredient) {
    $quantityG = (float) ($ingredient['quantity_g'] ?? 0);
    if ($quantityG <= 0) {
      continue;
    }

    $ratio = $quantityG / 100.0;
    $summary['calories'] += (float) ($ingredient['calories'] ?? 0) * $ratio;
    $summary['protein'] += (float) ($ingredient['protein'] ?? 0) * $ratio;
    $summary['carbs'] += (float) ($ingredient['carbs'] ?? 0) * $ratio;
    $summary['fat'] += (float) ($ingredient['fat'] ?? 0) * $ratio;
  }

  return [
    'calories' => round($summary['calories']),
    'protein' => round($summary['protein'], 1),
    'carbs' => round($summary['carbs'], 1),
    'fat' => round($summary['fat'], 1)
  ];
}

function meal_ingredient_text($ingredients)
{
  if (!$ingredients) {
    return 'Add ingredients to complete this meal.';
  }

  $names = array_map(function ($ingredient) {
    return $ingredient['name'];
  }, array_slice($ingredients, 0, 5));

  return implode(', ', $names);
}

function meal_image_url($meal, $ingredients)
{
  $mealPhotoUrl = mealdb_cooked_meal_image_url($meal, $ingredients);
  if ($mealPhotoUrl !== '') {
    return $mealPhotoUrl;
  }

  return meal_generated_image_url($meal, $ingredients);
}

function meal_generated_image_url($meal, $ingredients)
{
  $mealName = trim((string) ($meal['name'] ?? 'Healthy meal'));
  $ingredientNames = meal_ingredient_names($ingredients);
  $ingredientText = implode(', ', $ingredientNames);
  $prompt = 'cooked plated ' . ($mealName !== '' ? $mealName : 'healthy meal') . ' food photography';

  if ($ingredientText !== '') {
    $prompt .= ', made with ' . $ingredientText;
  }

  $prompt .= ', ready to eat, appetizing, restaurant style, no raw ingredients';
  $seedSource = strtolower($mealName . '|' . $ingredientText . '|' . ($meal['id'] ?? '0'));
  $seed = sprintf('%u', crc32($seedSource));

  return 'https://image.pollinations.ai/prompt/' . rawurlencode($prompt) . '?' . http_build_query([
    'width' => 640,
    'height' => 360,
    'seed' => $seed,
    'nologo' => 'true'
  ]);
}

function mealdb_cooked_meal_image_url($meal, $ingredients)
{
  static $cache = [];

  $mealName = trim((string) ($meal['name'] ?? ''));
  $ingredientNames = meal_ingredient_names($ingredients);
  $cacheKey = strtolower($mealName . '|' . implode('|', $ingredientNames));

  if (array_key_exists($cacheKey, $cache)) {
    return $cache[$cacheKey];
  }

  foreach (mealdb_search_terms($mealName) as $term) {
    $data = mealdb_fetch_json('https://www.themealdb.com/api/json/v1/1/search.php?s=' . rawurlencode($term));
    $mealResult = $data['meals'][0] ?? null;
    if (!empty($mealResult['strMealThumb'])) {
      $cache[$cacheKey] = $mealResult['strMealThumb'];
      return $cache[$cacheKey];
    }
  }

  $ingredientName = mealdb_best_ingredient_name($mealName, $ingredientNames);
  if ($ingredientName !== '') {
    $data = mealdb_fetch_json('https://www.themealdb.com/api/json/v1/1/filter.php?i=' . rawurlencode($ingredientName));
    $meals = $data['meals'] ?? [];
    if ($meals) {
      usort($meals, function ($a, $b) use ($mealName) {
        return mealdb_match_score($b['strMeal'] ?? '', $mealName) <=> mealdb_match_score($a['strMeal'] ?? '', $mealName);
      });

      if (!empty($meals[0]['strMealThumb'])) {
        $cache[$cacheKey] = $meals[0]['strMealThumb'];
        return $cache[$cacheKey];
      }
    }
  }

  $cache[$cacheKey] = '';
  return '';
}

function mealdb_search_terms($mealName)
{
  $mealName = trim((string) $mealName);
  if ($mealName === '') {
    return [];
  }

  $terms = [$mealName];
  $simple = preg_replace('/\b(with|and|plus|meal|bowl|plate)\b/i', ' ', $mealName);
  $simple = trim(preg_replace('/\s+/', ' ', $simple));

  if ($simple !== '' && strtolower($simple) !== strtolower($mealName)) {
    $terms[] = $simple;
  }

  return array_values(array_unique($terms));
}

function mealdb_best_ingredient_name($mealName, $ingredientNames)
{
  $text = strtolower($mealName . ' ' . implode(' ', $ingredientNames));
  $aliases = [
    'chicken' => 'Chicken',
    'potato' => 'Potatoes',
    'potatoes' => 'Potatoes',
    'rice' => 'Rice',
    'beef' => 'Beef',
    'salmon' => 'Salmon',
    'tuna' => 'Tuna',
    'egg' => 'Eggs',
    'eggs' => 'Eggs',
    'pasta' => 'Pasta',
    'tomato' => 'Tomatoes',
    'tomatoes' => 'Tomatoes',
    'broccoli' => 'Broccoli',
    'cheese' => 'Cheese'
  ];

  foreach ($aliases as $needle => $ingredientName) {
    if (preg_match('/\b' . preg_quote($needle, '/') . '\b/', $text)) {
      return $ingredientName;
    }
  }

  return '';
}

function mealdb_match_score($candidateName, $mealName)
{
  $candidate = strtolower((string) $candidateName);
  $words = preg_split('/[^a-z0-9]+/', strtolower((string) $mealName), -1, PREG_SPLIT_NO_EMPTY);
  $score = 0;

  foreach ($words as $word) {
    if (strlen($word) >= 3 && strpos($candidate, $word) !== false) {
      $score++;
    }
  }

  return $score;
}

function mealdb_fetch_json($url)
{
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 2,
      CURLOPT_TIMEOUT => 3,
      CURLOPT_FOLLOWLOCATION => true
    ]);
    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response !== false && $status >= 200 && $status < 300) {
      $data = json_decode($response, true);
      return is_array($data) ? $data : [];
    }
  }

  return [];
}

function meal_ingredient_names($ingredients)
{
  return array_values(array_filter(array_map(function ($ingredient) {
    return trim((string) ($ingredient['name'] ?? ''));
  }, array_slice($ingredients, 0, 5))));
}

function meal_placeholder_image_url($meal)
{
  $name = trim((string) ($meal['name'] ?? 'Meal image'));
  $name = $name !== '' ? $name : 'Meal image';
  $name = htmlspecialchars(substr($name, 0, 42), ENT_QUOTES, 'UTF-8');
  $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360">'
    . '<defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop stop-color="#e6f7ed"/><stop offset="1" stop-color="#ffffff"/></linearGradient></defs>'
    . '<rect width="640" height="360" fill="url(#g)"/>'
    . '<circle cx="160" cy="120" r="74" fill="#cbeed9"/>'
    . '<circle cx="480" cy="240" r="92" fill="#dff5e8"/>'
    . '<rect x="150" y="112" width="340" height="142" rx="28" fill="#ffffff" stroke="#b8dfc7" stroke-width="6"/>'
    . '<circle cx="240" cy="180" r="34" fill="#2f8f37"/>'
    . '<circle cx="318" cy="180" r="24" fill="#b2d476"/>'
    . '<circle cx="390" cy="180" r="30" fill="#0d8751"/>'
    . '<text x="320" y="300" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="700" fill="#173d24">' . $name . '</text>'
    . '</svg>';

  return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}
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
  <div class="app" data-view="dashboard" data-page="meals">
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
          <a class="nav-link active" href="meals.php">Meals</a>
          <a class="nav-link" href="exercises.php">Exercises</a>
          <a class="nav-link" href="store.php">Store</a>
          <a class="nav-link" href="profile.php">Profile</a>
          <a class="nav-link" href="support.php">Support</a>
          <?php if (($user['role'] ?? 'user') === 'admin'): ?>
            <a class="nav-link portal-link" href="access.php?target=admin"><span class="nav-icon">AP</span>Admin Panel</a>
          <?php endif; ?>
        </nav>
      </aside>

      <section class="content">
        <header class="page-head">
          <div class="page-head-copy">
            <span class="page-kicker">Meal studio</span>
            <h2>Build meals that work in real life.</h2>
            <p>Create budget-friendly meals, save ingredients, and open cooking guidance when it is time to prep.</p>
          </div>
          <div class="page-head-actions">
            <a class="btn ghost" href="store.php">Find products</a>
            <a class="btn ghost" href="logout.php">Log out</a>
          </div>
        </header>

        <div class="insight-row meal-insights" aria-label="Meal overview">
          <div class="insight-card">
            <span>Saved meals</span>
            <strong><?= count($meals) ?></strong>
            <small>Total custom meals</small>
          </div>
          <div class="insight-card">
            <span>Ingredients</span>
            <strong><?= count($ingredients) ?></strong>
            <small>Available to build with</small>
          </div>
          <div class="insight-card">
            <span>Avg protein</span>
            <strong><?= htmlspecialchars(number_format((float) $averageProteinG, 1)) ?>g</strong>
            <small>Across saved meals</small>
          </div>
        </div>

        <section class="card meal-composer-card">
          <form method="post" id="meal-form" class="meal-composer-form" novalidate>
            <input type="hidden" name="action" value="add_meal" />
            <div class="meal-widget-head">
              <h3>Add Custom Meal</h3>
              <div class="meal-widget-actions">
                <button class="btn soft tiny" type="reset">Clear</button>
                <button class="btn primary tiny" type="submit">Save Meal</button>
              </div>
            </div>

            <div class="meal-composer-grid">
              <div class="meal-composer-fields">
                <div class="meal-form-row">
                  <label>
                    <span>Meal name</span>
                    <input type="text" name="meal_name" id="meal-name" placeholder="e.g. Chicken breast with rice" />
                    <small class="error" data-error-for="meal-name"></small>
                  </label>
                  <label>
                    <span>Meal type</span>
                    <select name="meal_type" id="meal-type">
                      <option value="">Select meal type</option>
                      <option value="Breakfast">Breakfast</option>
                      <option value="Lunch">Lunch</option>
                      <option value="Dinner">Dinner</option>
                      <option value="Snack">Snack</option>
                    </select>
                    <small class="error" data-error-for="meal-type"></small>
                  </label>
                </div>

                <div class="meal-builder">
                  <span>Ingredients (optional)</span>
                  <div id="meal-ingredients-list" class="meal-builder-list">
                    <div class="meal-ingredient-grid meal-builder-row">
                      <select name="ingredient_id[]" id="meal-ingredient">
                        <option value="">Choose ingredient</option>
                        <?php foreach ($ingredients as $ing): ?>
                          <option
                            value="<?= e($ing['id']) ?>"
                            data-calories="<?= e($ing['calories'] ?? 0) ?>"
                            data-protein="<?= e($ing['protein'] ?? 0) ?>"
                            data-carbs="<?= e($ing['carbs'] ?? 0) ?>"
                            data-fat="<?= e($ing['fat'] ?? 0) ?>">
                            <?= e($ing['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <input type="text" name="quantity_g[]" id="meal-qty" placeholder="100" />
                      <span class="unit-pill">g</span>
                      <button class="icon-btn danger remove-ingredient" type="button" hidden aria-label="Remove ingredient">x</button>
                    </div>
                  </div>
                  <div class="selected-ingredient-chips" id="selected-ingredient-chips" aria-live="polite"></div>
                  <button class="btn soft tiny add-ingredient-btn" type="button" id="add-ingredient-row">Add ingredient</button>
                  <small class="error" data-error-for="meal-qty"></small>
                </div>
              </div>

              <aside class="nutrition-preview" aria-label="Meal nutrition preview">
                <span class="preview-kicker">Preview</span>
                <div class="preview-grid">
                  <div>
                    <small>Protein</small>
                    <strong><span id="preview-protein">0.0</span>g</strong>
                  </div>
                  <div>
                    <small>Carbs</small>
                    <strong><span id="preview-carbs">0.0</span>g</strong>
                  </div>
                  <div>
                    <small>Fat</small>
                    <strong><span id="preview-fat">0.0</span>g</strong>
                  </div>
                  <div>
                    <small>Calories</small>
                    <strong><span id="preview-calories">0</span> kcal</strong>
                  </div>
                </div>
                <p>Nutrition values are estimated from the ingredients you choose.</p>
              </aside>
            </div>
          </form>
        </section>

        <?php
          $profileBudget = (float) ($profileBudget ?? 0);
          $aiBudgetValue = (float) ($aiForm['max_budget'] ?? $profileBudget);
          $aiBudgetValue = $profileBudget > 0 ? min($profileBudget, max(0, $aiBudgetValue)) : 0;
        ?>
        <section class="card ai-meal-card" id="ai-meal-generator">
          <form method="post" class="ai-meal-controls ai-generator-form">
            <input type="hidden" name="action" value="generate_ai_meals" />
            <h3>AI Meal Generator</h3>
            <p>Let our AI create a personal meal using your ingredients and preferences.</p>
            <div class="ai-control-grid">
              <label>
                <span>Use my ingredients</span>
                <select>
                  <option>All available ingredients (<?= count($ingredients) ?>)</option>
                  <option>High protein only</option>
                  <option>Budget friendly only</option>
                </select>
              </label>
              <label>
                <span>Meal type</span>
                <select name="ai_meal_type">
                  <?php foreach (['Breakfast', 'Lunch', 'Dinner', 'Snack'] as $mealTypeOption): ?>
                    <option value="<?= e($mealTypeOption) ?>" <?= ($aiForm['meal_type'] ?? 'Lunch') === $mealTypeOption ? 'selected' : '' ?>><?= e($mealTypeOption) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>
                <span>Protein goal</span>
                <select name="ai_protein_goal">
                  <?php foreach (['High (30g+ protein)', 'Balanced', 'Light meal'] as $proteinGoalOption): ?>
                    <option value="<?= e($proteinGoalOption) ?>" <?= ($aiForm['protein_goal'] ?? 'High (30g+ protein)') === $proteinGoalOption ? 'selected' : '' ?>><?= e($proteinGoalOption) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>

            <div class="ai-budget-control">
              <div>
                <span>Max budget</span>
                <strong><output id="ai-budget-output"><?= e(number_format($aiBudgetValue, 0)) ?></output> / <?= e(number_format($profileBudget, 0)) ?></strong>
              </div>
              <input
                type="range"
                id="ai-budget-range"
                name="ai_max_budget"
                min="0"
                max="<?= e($profileBudget) ?>"
                step="1"
                value="<?= e($aiBudgetValue) ?>"
                <?= $profileBudget <= 0 ? 'disabled' : '' ?> />
            </div>
            <?php if ($profileBudget <= 0): ?>
              <p class="ai-budget-note">Add your monthly food budget in the profile page to unlock budget control.</p>
            <?php endif; ?>
            <button class="btn primary ai-generate-btn" type="submit">Generate Meal</button>
          </form>
        </section>

        <?php if ($aiMealError !== ''): ?>
          <div class="ai-generator-alert"><?= e($aiMealError) ?></div>
        <?php endif; ?>

        <?php if ($aiMealSuggestions): ?>
          <section class="ai-results-panel" aria-label="Generated meals">
            <div class="ai-generated-grid">
              <?php foreach ($aiMealSuggestions as $suggestion): ?>
                <?php
                  $suggestionMeal = ['name' => $suggestion['name'], 'id' => crc32($suggestion['name'])];
                  $suggestionImage = meal_image_url($suggestionMeal, $suggestion['ingredients']);
                  $suggestionPlaceholder = meal_placeholder_image_url($suggestionMeal);
                  $suggestionMacros = $suggestion['macros'];
                ?>
                <article class="ai-result-card">
                  <div class="ai-result-image generated-meal-art">
                    <img src="<?= e($suggestionImage) ?>" alt="<?= e($suggestion['name']) ?>" loading="lazy" onerror="this.onerror=null;this.closest('.generated-meal-art').classList.add('image-error');this.src='<?= e($suggestionPlaceholder) ?>';" />
                  </div>
                  <div class="ai-result-body">
                    <span class="ai-result-kicker"><?= e($suggestion['type']) ?></span>
                    <h4><?= e($suggestion['name']) ?></h4>
                    <p><?= e($suggestion['description']) ?></p>
                    <div class="macro-strip ai-result-macros">
                      <span><small>Protein</small><?= e(number_format((float) $suggestionMacros['protein'], 1)) ?>g</span>
                      <span><small>Carbs</small><?= e(number_format((float) $suggestionMacros['carbs'], 1)) ?>g</span>
                      <span><small>Fat</small><?= e(number_format((float) $suggestionMacros['fat'], 1)) ?>g</span>
                      <span><small>Calories</small><?= e(number_format((float) $suggestionMacros['calories'], 0)) ?> kcal</span>
                    </div>
                    <div class="ai-result-cost">Estimated cost: <?= e(number_format((float) $suggestionMacros['cost'], 2)) ?></div>
                    <ul class="ai-result-ingredients">
                      <?php foreach ($suggestion['ingredients'] as $ingredient): ?>
                        <li><?= e($ingredient['name']) ?> - <?= e(number_format((float) $ingredient['quantity_g'], 0)) ?>g</li>
                      <?php endforeach; ?>
                    </ul>
                    <form method="post">
                      <input type="hidden" name="action" value="add_ai_meal" />
                      <input type="hidden" name="ai_meal_name" value="<?= e($suggestion['name']) ?>" />
                      <input type="hidden" name="ai_meal_type" value="<?= e($suggestion['type']) ?>" />
                      <?php foreach ($suggestion['ingredients'] as $ingredient): ?>
                        <input type="hidden" name="ai_ingredient_id[]" value="<?= e($ingredient['id']) ?>" />
                        <input type="hidden" name="ai_quantity_g[]" value="<?= e($ingredient['quantity_g']) ?>" />
                      <?php endforeach; ?>
                      <button class="btn primary" type="submit">Add to My Meals</button>
                    </form>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <section class="card meal-list-card">
          <div class="meal-widget-head">
            <div>
              <h3>My Meals</h3>
              <p>Your saved meals with cooking videos and details.</p>
            </div>
            <div class="meal-widget-actions">
              <a class="btn soft tiny with-icon organize-btn" href="<?= e($organizeHref) ?>" aria-label="Organize meals by protein">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                  <path fill="currentColor" d="M7 4h2v14l1.5-1.5 1.4 1.4L8 22 4.1 17.9l1.4-1.4L7 18V4zm10 2h-6V4h6c1.7 0 3 1.3 3 3v4c0 1.7-1.3 3-3 3h-2v4h-2V8h4c.6 0 1-.4 1-1V7c0-.6-.4-1-1-1zm-2 14h2v2h-2v-2z"/>
                </svg>
                <span><?= e($organizeLabel) ?></span>
              </a>
              <a class="btn primary tiny" href="#meal-form">Add Meal</a>
            </div>
          </div>
          <?php if (!$meals): ?>
            <p class="muted">No meals yet.</p>
          <?php else: ?>
            <?php if ($isProteinSort): ?>
              <p class="muted meals-sort-hint">Organized by protein from high to low.</p>
            <?php endif; ?>
            <div class="meal-list-shell">
              <?php foreach ($meals as $meal): ?>
                <?php
                  $ings = $mealIngredientsMap[$meal['id']] ?? [];
                  $coach = $mealCoachMap[$meal['id']] ?? null;
                  $macros = meal_macro_summary($ings);
                  $mealImageUrl = meal_image_url($meal, $ings);
                  $mealPlaceholderUrl = meal_placeholder_image_url($meal);
                ?>
                <article class="meal-row<?= ($coach && $coach['video']) ? ' has-video' : ' no-video' ?>">
                  <div class="meal-row-summary">
                    <div class="meal-thumb generated-meal-art">
                      <img src="<?= e($mealImageUrl) ?>" alt="<?= e($meal['name']) ?>" loading="eager" onerror="this.onerror=null;this.closest('.generated-meal-art').classList.add('image-error');this.src='<?= e($mealPlaceholderUrl) ?>';" />
                    </div>
                    <div class="meal-row-details">
                      <form method="post" class="inline-edit meal-edit-form meal-row-edit" novalidate>
                        <input type="hidden" name="action" value="update_meal" />
                        <input type="hidden" name="meal_id" value="<?= e($meal['id']) ?>" />
                        <div class="meal-row-title">
                          <label>
                            <span>Meal name</span>
                            <input type="text" name="meal_name" value="<?= e($meal['name']) ?>" />
                          </label>
                          <span class="meal-type-badge"><?= e(strtoupper((string) $meal['type'])) ?></span>
                          <span class="protein-badge"><?= e(number_format((float) ($mealProteinMap[$meal['id']] ?? 0), 1)) ?>g protein</span>
                        </div>
                        <input type="hidden" name="meal_type" value="<?= e($meal['type']) ?>" />
                        <p class="meal-ingredients-line"><?= e(meal_ingredient_text($ings)) ?></p>
                        <div class="meal-row-meta">
                          <span><?= e(number_format((float) $macros['calories'], 0)) ?> kcal</span>
                          <span><?= e(number_format((float) $macros['carbs'], 1)) ?>g carbs</span>
                          <span><?= e(number_format((float) $macros['fat'], 1)) ?>g fat</span>
                        </div>
                        <button class="icon-btn meal-action-btn" type="submit" aria-label="Save meal">
                          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zM7 5h8v5H7V5zm10 14H7v-6h10v6z"/>
                          </svg>
                        </button>
                      </form>

                      <form method="post" class="meal-ingredient-form compact-add-form" novalidate>
                        <input type="hidden" name="action" value="add_meal_ingredient" />
                        <input type="hidden" name="meal_id" value="<?= e($meal['id']) ?>" />
                        <span>Add ingredient</span>
                        <div class="meal-ingredient-grid">
                          <select name="ingredient_id">
                            <option value="">Choose ingredient</option>
                            <?php foreach ($ingredients as $ing): ?>
                              <option value="<?= e($ing['id']) ?>"><?= e($ing['name']) ?></option>
                            <?php endforeach; ?>
                          </select>
                          <input type="text" name="quantity_g" placeholder="100" />
                          <button class="icon-btn" type="submit">Add</button>
                        </div>
                      </form>
                    </div>
                    <form method="post" class="meal-delete-form">
                      <input type="hidden" name="action" value="delete_meal" />
                      <input type="hidden" name="meal_id" value="<?= e($meal['id']) ?>" />
                      <button class="icon-btn danger meal-action-btn" type="submit" aria-label="Delete meal">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                          <path fill="currentColor" d="M9 3h6l1 2h4v2H4V5h4l1-2zm-2 6h10l-.8 12H7.8L7 9zm3 2 .2 8h1.6l-.2-8H10zm4 0-.2 8h1.6l.2-8H14z"/>
                        </svg>
                      </button>
                    </form>
                  </div>

                  <?php if ($coach && $coach['video']): ?>
                    <div class="meal-row-media">
                        <div class="meal-video">
                          <iframe
                            src="https://www.youtube.com/embed/<?= e($coach['video']['id']) ?>"
                            title="<?= e($coach['video']['title']) ?>"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                        </div>
                    </div>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
        <?php include __DIR__ . '/user_support_footer.php'; ?>
      </section>
    </main>
  </div>

  <script>
    const form = document.getElementById('meal-form');
    const mealName = document.getElementById('meal-name');
    const mealType = document.getElementById('meal-type');
    const ingredientsList = document.getElementById('meal-ingredients-list');
    const addIngredientRow = document.getElementById('add-ingredient-row');
    const selectedIngredientChips = document.getElementById('selected-ingredient-chips');
    const previewProtein = document.getElementById('preview-protein');
    const previewCarbs = document.getElementById('preview-carbs');
    const previewFat = document.getElementById('preview-fat');
    const previewCalories = document.getElementById('preview-calories');

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

    function updateNutritionPreview() {
      const totals = { calories: 0, protein: 0, carbs: 0, fat: 0 };
      const chips = [];
      const rows = ingredientsList.querySelectorAll('.meal-builder-row');

      rows.forEach((row) => {
        const ingredient = row.querySelector('select');
        const quantity = row.querySelector('input');
        const option = ingredient.options[ingredient.selectedIndex];
        const grams = Number(quantity.value);

        if (!option || ingredient.value === '' || !Number.isFinite(grams) || grams <= 0) {
          return;
        }

        const ratio = grams / 100;
        totals.calories += Number(option.dataset.calories || 0) * ratio;
        totals.protein += Number(option.dataset.protein || 0) * ratio;
        totals.carbs += Number(option.dataset.carbs || 0) * ratio;
        totals.fat += Number(option.dataset.fat || 0) * ratio;
        chips.push(`${option.textContent.trim()} ${grams}g`);
      });

      previewProtein.textContent = totals.protein.toFixed(1);
      previewCarbs.textContent = totals.carbs.toFixed(1);
      previewFat.textContent = totals.fat.toFixed(1);
      previewCalories.textContent = Math.round(totals.calories);

      selectedIngredientChips.textContent = '';
      chips.forEach((chipText) => {
        const chip = document.createElement('span');
        chip.className = 'ingredient-chip';
        chip.textContent = chipText;
        selectedIngredientChips.appendChild(chip);
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
      updateNutritionPreview();
    });

    ingredientsList.addEventListener('click', (e) => {
      if (!e.target.classList.contains('remove-ingredient')) return;
      e.target.closest('.meal-builder-row').remove();
      refreshRemoveButtons();
      updateNutritionPreview();
    });

    ingredientsList.addEventListener('input', updateNutritionPreview);
    ingredientsList.addEventListener('change', updateNutritionPreview);

    form.addEventListener('reset', () => {
      ingredientsList.querySelectorAll('.meal-builder-row').forEach((row, index) => {
        if (index > 0) row.remove();
      });
      clearError('meal-name');
      clearError('meal-type');
      clearError('meal-qty');
      window.setTimeout(() => {
        refreshRemoveButtons();
        updateNutritionPreview();
      }, 0);
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
    updateNutritionPreview();

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

    const aiBudgetRange = document.getElementById('ai-budget-range');
    const aiBudgetOutput = document.getElementById('ai-budget-output');
    if (aiBudgetRange && aiBudgetOutput) {
      const syncAiBudget = () => {
        aiBudgetOutput.textContent = Number(aiBudgetRange.value || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
      };
      aiBudgetRange.addEventListener('input', syncAiBudget);
      syncAiBudget();
    }
  </script>
  <script src="user-panel.js"></script>
</body>
</html>
