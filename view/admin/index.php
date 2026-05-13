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
  'productCategoryIds' => $productCategoryIds,
  'productCategoryNames' => $productCategoryNames,
  'responsesByRequest' => $responsesByRequest
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
            <form method="post" class="admin-form compact category-form" novalidate>
              <input type="hidden" name="action" value="add_category" />
              <input type="text" name="c_name" placeholder="Category name, e.g. Fruit" required minlength="2" maxlength="150" pattern="[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ &,'.-]*" />
              <button class="icon-btn" type="submit">Add</button>
              <small class="error form-error">Use 2-150 letters only. Spaces, &, comma, apostrophe, dot and dash are allowed.</small>
            </form>
            <div class="admin-list">
              <?php if (!$categories): ?>
                <article class="admin-item empty-admin-item">
                  <div class="admin-item-main">
                    <h4>No categories yet</h4>
                    <p>Create the first food category, then assign products to it.</p>
                  </div>
                </article>
              <?php endif; ?>
              <?php foreach ($categories as $c): ?>
                <article class="admin-item category-admin-item">
                  <form method="post" class="admin-form compact category-form" novalidate>
                    <input type="hidden" name="action" value="update_category" />
                    <input type="hidden" name="category_id" value="<?= e($c['id']) ?>" />
                    <input type="text" name="c_name" value="<?= e($c['name']) ?>" required minlength="2" maxlength="150" pattern="[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ &,'.-]*" />
                    <button class="icon-btn" type="submit">Save</button>
                    <small class="error form-error">Use 2-150 letters only. Spaces, &, comma, apostrophe, dot and dash are allowed.</small>
                  </form>
                  <span class="category-count"><?= (int) ($c['product_count'] ?? 0) ?> products</span>
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
              <input type="text" name="p_name" id="p-name" placeholder="Name" required minlength="2" maxlength="150" />
              <input type="text" name="p_description" id="p-description" placeholder="Description" required minlength="4" maxlength="1000" />
              <input type="number" name="p_price" id="p-price" placeholder="Price" required min="0" max="999999" step="0.01" />
              <input type="number" name="p_stock" id="p-stock" placeholder="Stock" required min="0" max="999999" step="1" />
              <div class="auto-image-note">Image is selected automatically from the product name.</div>
              <div class="category-picker" id="p-categories" role="radiogroup" aria-label="Product category">
                <?php foreach ($categories as $c): ?>
                  <label>
                    <input type="radio" name="category_id" value="<?= e($c['id']) ?>" />
                    <span><?= e($c['name']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <button class="icon-btn" type="submit">Add</button>
              <small class="error" data-error-for="p-name"></small>
              <small class="error" data-error-for="p-description"></small>
              <small class="error" data-error-for="p-price"></small>
              <small class="error" data-error-for="p-stock"></small>
              <small class="error" data-error-for="p-categories"></small>
            </form>
            <div class="admin-list">
              <?php foreach ($products as $p): ?>
                <article class="admin-item product-admin-item">
                  <div class="admin-item-main">
                    <?php $pcatNames = $productCategoryNames[$p['id']] ?? []; ?>
                    <div class="category-chip-row" aria-label="Product categories">
                      <?php if ($pcatNames): ?>
                        <?php foreach ($pcatNames as $categoryName): ?>
                          <span><?= e($categoryName) ?></span>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <span class="muted-chip">No category</span>
                      <?php endif; ?>
                    </div>
                    <form method="post" class="admin-form grid-form">
                      <input type="hidden" name="action" value="update_product" />
                      <input type="hidden" name="product_id" value="<?= e($p['id']) ?>" />
                      <input type="text" name="p_name" value="<?= e($p['name']) ?>" required minlength="2" maxlength="150" />
                      <input type="text" name="p_description" value="<?= e($p['description']) ?>" required minlength="4" maxlength="1000" />
                      <input type="number" name="p_price" value="<?= e($p['price']) ?>" required min="0" max="999999" step="0.01" />
                      <input type="number" name="p_stock" value="<?= e($p['stock']) ?>" required min="0" max="999999" step="1" />
                      <input type="hidden" name="p_image_url" value="<?= e($p['image_url']) ?>" />
                      <div class="auto-image-note">Image updates automatically for auto-selected product photos.</div>
                      <button class="icon-btn" type="submit">Save</button>
                    </form>
                    <form method="post" class="admin-form compact product-category-form" novalidate>
                      <input type="hidden" name="action" value="set_product_categories" />
                      <input type="hidden" name="product_id" value="<?= e($p['id']) ?>" />
                      <div class="category-picker compact-picker" role="radiogroup" aria-label="Product category">
                        <?php $pcatIds = $productCategoryIds[$p['id']] ?? []; ?>
                        <?php foreach ($categories as $c): ?>
                          <label>
                            <input type="radio" name="category_id" value="<?= e($c['id']) ?>" <?= in_array($c['id'], $pcatIds) ? 'checked' : '' ?> />
                            <span><?= e($c['name']) ?></span>
                          </label>
                        <?php endforeach; ?>
                      </div>
                      <button class="icon-btn" type="submit">Save Categories</button>
                      <small class="error form-error">Select one category.</small>
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
              <input type="text" name="youtube_url" placeholder="YouTube Tutorial URL (optional)" />
              <button class="icon-btn" type="submit">Add</button>
            </form>
            <div class="admin-list">
              <?php foreach ($exercises as $ex): ?>
                <article class="admin-item">
                  <form method="post" class="admin-form compact" novalidate>
                    <input type="hidden" name="action" value="update_exercise" />
                    <input type="hidden" name="exercise_id" value="<?= e($ex['id']) ?>" />
                    <input type="text" name="name" value="<?= e($ex['name']) ?>" />
                    <input type="text" name="youtube_url" value="<?= e($ex['youtube_url'] ?? '') ?>" placeholder="YouTube Tutorial URL (optional)" />
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
            <div class="store-filters support-admin-filters" aria-label="Support type filters">
              <button class="filter-chip active" type="button" data-support-filter="all">All</button>
              <button class="filter-chip" type="button" data-support-filter="Exercise">Exercise</button>
              <button class="filter-chip" type="button" data-support-filter="Meal">Meal</button>
              <button class="filter-chip" type="button" data-support-filter="Profile">Profile</button>
            </div>
            <p class="muted store-empty" id="support-empty-filter" hidden></p>
            <div class="admin-list">
              <?php foreach ($requests as $r): ?>
                <?php $responses = $responsesByRequest[(int) $r['id']] ?? []; ?>
                <?php
                  $aiCategory = trim((string) ($r['ai_category'] ?? ''));
                  $aiPriority = trim((string) ($r['ai_priority'] ?? ''));
                  $aiSummary = trim((string) ($r['ai_summary'] ?? ''));
                  $aiSolution = trim((string) ($r['ai_suggested_solution'] ?? ''));
                  $hasAiSupportData = $aiCategory !== '' || $aiPriority !== '' || $aiSummary !== '' || $aiSolution !== '';
                ?>
                <article class="admin-item support-admin-item" data-support-type="<?= e($r['type']) ?>">
                  <div class="admin-item-main">
                    <h4><?= e($r['issue_title']) ?></h4>
                    <p><?= e($r['description']) ?></p>
                    <div class="meta">
                      <span class="support-type-pill"><?= e($r['type']) ?></span>
                      <span><?= e($r['email']) ?></span>
                      <span class="status <?= e($r['status']) ?>"><?= e($r['status']) ?></span>
                    </div>
                    <div class="support-admin-identity">
                      <span><?= e($r['first_name'] . ' ' . $r['last_name']) ?></span>
                      <span><?= e($r['created_at']) ?></span>
                    </div>
                    <?php if ($hasAiSupportData): ?>
                      <div class="support-ai-admin-card">
                        <div class="support-ai-admin-head">
                          <span>AI support summary</span>
                          <?php if ($aiPriority !== ''): ?>
                            <strong><?= e($aiPriority) ?></strong>
                          <?php endif; ?>
                        </div>
                        <div class="support-ai-admin-grid">
                          <?php if ($aiCategory !== ''): ?>
                            <span><b>Category</b><?= e($aiCategory) ?></span>
                          <?php endif; ?>
                          <?php if ($aiSummary !== ''): ?>
                            <span><b>Summary</b><?= e($aiSummary) ?></span>
                          <?php endif; ?>
                        </div>
                        <?php if ($aiSolution !== ''): ?>
                          <div class="support-ai-admin-solution">
                            <b>Suggested solution</b>
                            <p><?= e($aiSolution) ?></p>
                          </div>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                    <form method="post" class="admin-form compact support-response-create" novalidate>
                      <input type="hidden" name="action" value="add_response" />
                      <input type="hidden" name="request_id" value="<?= e($r['id']) ?>" />
                      <div class="support-ai-reply-actions">
                        <button class="icon-btn support-ai-reply-btn" type="button" data-request-id="<?= e($r['id']) ?>">Generate AI Reply</button>
                        <span class="support-ai-reply-status" aria-live="polite"></span>
                      </div>
                      <textarea name="message" rows="3" placeholder="Response message"></textarea>
                      <button class="icon-btn" type="submit">Respond</button>
                      <small class="error form-error">Response message must be at least 2 characters.</small>
                    </form>

                    <?php if ($responses): ?>
                      <div class="support-response-thread admin-response-thread">
                        <span class="support-thread-label">Response thread</span>
                        <?php foreach ($responses as $response): ?>
                          <div class="support-response-item admin-response-item">
                            <div class="support-response-meta">
                              <strong><?= e($response['admin_name'] ?? 'Admin') ?></strong>
                              <span><?= e($response['responded_at']) ?></span>
                            </div>
                            <form method="post" class="admin-form compact support-response-edit" novalidate>
                              <input type="hidden" name="action" value="update_response" />
                              <input type="hidden" name="response_id" value="<?= e($response['id']) ?>" />
                              <textarea name="message" rows="2"><?= e($response['message']) ?></textarea>
                              <button class="icon-btn" type="submit">Save</button>
                              <small class="error form-error">Response message must be at least 2 characters.</small>
                            </form>
                            <form method="post">
                              <input type="hidden" name="action" value="delete_response" />
                              <input type="hidden" name="response_id" value="<?= e($response['id']) ?>" />
                              <button class="icon-btn danger" type="submit">Delete</button>
                            </form>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
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
    const productCategories = document.getElementById('p-categories');
    const ingredientForm = document.getElementById('ingredient-form');
    const ingredientName = document.getElementById('ing-name');
    const ingredientCalories = document.getElementById('ing-calories');
    const ingredientProtein = document.getElementById('ing-protein');
    const ingredientCarbs = document.getElementById('ing-carbs');
    const ingredientFat = document.getElementById('ing-fat');
    const ingredientPrice = document.getElementById('ing-price');
    const supportFilterButtons = Array.from(document.querySelectorAll('[data-support-filter]'));
    const supportCards = Array.from(document.querySelectorAll('.support-admin-item'));
    const supportEmptyFilter = document.getElementById('support-empty-filter');

    function setError(id, message) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = message;
    }

    function clearError(id) {
      const el = document.querySelector(`[data-error-for="${id}"]`);
      if (el) el.textContent = '';
    }

    function validateCategoryName(value) {
      return /^[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ &,'.-]*$/.test(value) && value.length >= 2 && value.length <= 150;
    }

    document.querySelectorAll('.category-form').forEach((categoryForm) => {
      const input = categoryForm.querySelector('input[name="c_name"]');
      const error = categoryForm.querySelector('.error');

      categoryForm.addEventListener('submit', (e) => {
        const value = input.value.trim();
        const ok = validateCategoryName(value);
        input.value = value;
        if (error) error.classList.toggle('is-visible', !ok);
        if (!ok) e.preventDefault();
      });
    });

    document.querySelectorAll('.product-category-form').forEach((categoryForm) => {
      const choices = Array.from(categoryForm.querySelectorAll('input[name="category_id"]'));
      const error = categoryForm.querySelector('.error');

      categoryForm.addEventListener('submit', (e) => {
        const ok = choices.some((choice) => choice.checked);
        if (error) error.classList.toggle('is-visible', !ok);
        if (!ok) e.preventDefault();
      });
    });

    document.querySelectorAll('.support-response-create, .support-response-edit').forEach((responseForm) => {
      const messageInput = responseForm.querySelector('[name="message"]');
      const error = responseForm.querySelector('.error');

      responseForm.addEventListener('submit', (e) => {
        const ok = messageInput && messageInput.value.trim().length >= 2;
        if (error) error.classList.toggle('is-visible', !ok);
        if (!ok) e.preventDefault();
      });
    });

    document.querySelectorAll('.support-ai-reply-btn').forEach((button) => {
      button.addEventListener('click', async () => {
        const responseForm = button.closest('.support-response-create');
        const messageInput = responseForm ? responseForm.querySelector('[name="message"]') : null;
        const status = responseForm ? responseForm.querySelector('.support-ai-reply-status') : null;
        if (!messageInput) return;

        button.disabled = true;
        button.textContent = 'Generating...';
        if (status) {
          status.textContent = 'Preparing reply...';
          status.classList.remove('is-error');
        }

        try {
          const payload = new FormData();
          payload.append('action', 'generate_support_ai_reply');
          payload.append('request_id', button.dataset.requestId || '');
          const response = await fetch('index.php', {
            method: 'POST',
            body: payload,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });
          const data = await response.json();
          if (!response.ok || !data.success) {
            throw new Error(data.message || 'AI reply generation is unavailable.');
          }

          messageInput.value = data.reply;
          messageInput.focus();
          messageInput.dispatchEvent(new Event('input', { bubbles: true }));
          if (status) status.textContent = 'Reply generated. Edit it before sending.';
        } catch (error) {
          if (status) {
            status.textContent = `${error.message}`;
            status.classList.add('is-error');
          }
        } finally {
          button.disabled = false;
          button.textContent = 'Generate AI Reply';
        }
      });
    });

    form.addEventListener('submit', (e) => {
      let ok = true;
      if (nameField.value.trim().length < 2 || nameField.value.trim().length > 150) { setError('p-name', 'Name must be 2 to 150 characters.'); ok = false; } else clearError('p-name');
      if (descField.value.trim().length < 4 || descField.value.trim().length > 1000) { setError('p-description', 'Description must be 4 to 1000 characters.'); ok = false; } else clearError('p-description');
      if (!isPositiveNumber(priceField, 999999)) { setError('p-price', 'Enter a price from 0 to 999999.'); ok = false; } else clearError('p-price');
      if (!isPositiveNumber(stockField, 999999) || !Number.isInteger(Number(stockField.value))) { setError('p-stock', 'Stock must be a whole number from 0 to 999999.'); ok = false; } else clearError('p-stock');
      if (productCategories && !Array.from(productCategories.querySelectorAll('input[name="category_id"]')).some((choice) => choice.checked)) { setError('p-categories', 'Choose one category.'); ok = false; } else clearError('p-categories');
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
    const jumpLinks = Array.from(document.querySelectorAll('.admin-nav .nav-link[href^="#"], .admin-signal[href^="#"]'));
    const panels = Array.from(document.querySelectorAll('.admin-panel[id]'));
    const panelIds = panels.map((panel) => panel.id);

    function setActivePanel(id, pulse = false) {
      const targetId = panelIds.includes(id) ? id : 'users';
      navLinks.forEach((link) => link.classList.toggle('active', link.getAttribute('href') === `#${targetId}`));
      const activeLink = navLinks.find((link) => link.getAttribute('href') === `#${targetId}`);
      panels.forEach((panel) => {
        panel.hidden = panel.id !== targetId;
      });

      if (activeLink && nav) {
        nav.style.setProperty('--active-top', `${activeLink.offsetTop}px`);
        nav.style.setProperty('--active-height', `${activeLink.offsetHeight}px`);
      }

      if (pulse) {
        const panel = document.getElementById(targetId);
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
        document.querySelector('.admin-content').scrollIntoView({ behavior: 'smooth', block: 'start' });
        history.replaceState(null, '', `#${id}`);
      });
    });

    setActivePanel(location.hash ? location.hash.slice(1) : 'users');
    window.addEventListener('resize', () => {
      const active = navLinks.find((link) => link.classList.contains('active'));
      if (active) setActivePanel(active.getAttribute('href').slice(1));
    });

    supportFilterButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const filter = button.dataset.supportFilter;
        let visibleCount = 0;
        supportFilterButtons.forEach((item) => item.classList.toggle('active', item === button));

        supportCards.forEach((card) => {
          const show = filter === 'all' || card.dataset.supportType === filter;
          card.hidden = !show;
          if (show) visibleCount += 1;
        });

        if (supportEmptyFilter) {
          supportEmptyFilter.textContent = visibleCount ? '' : 'No support requests found for this type.';
          supportEmptyFilter.hidden = visibleCount > 0;
        }
      });
    });
  </script>
</body>
</html>
