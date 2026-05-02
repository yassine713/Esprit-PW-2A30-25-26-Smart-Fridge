<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/StorePageController.php';
require_login();

$user = $_SESSION['user'];
$storePageController = new StorePageController();
['products' => $products, 'categories' => $categories] = $storePageController->load();

if (!function_exists('e')) {
  function e($value)
  {
    return htmlspecialchars((string) $value);
  }
}

function product_image_url($product)
{
  if (!empty($product['image_url'])) {
    return $product['image_url'];
  }

  return 'https://www.themealdb.com/images/ingredients/' . rawurlencode(product_image_name($product['name'] ?? 'Chicken')) . '.png';
}

function product_image_name($name)
{
  $value = strtolower(trim($name));
  $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value);
  $value = trim(preg_replace('/\s+/', ' ', $value));

  $aliases = [
    'potato' => 'Potatoes',
    'potatoes' => 'Potatoes',
    'tomato' => 'Tomatoes',
    'tomatoes' => 'Tomatoes',
    'apple' => 'Apple',
    'apples' => 'Apple',
    'banana' => 'Banana',
    'bananas' => 'Banana',
    'orange' => 'Orange',
    'oranges' => 'Orange',
    'lemon' => 'Lemon',
    'lemons' => 'Lemon',
    'onion' => 'Onion',
    'onions' => 'Onion',
    'carrot' => 'Carrots',
    'carrots' => 'Carrots',
    'chicken breast' => 'Chicken Breast',
    'chicken' => 'Chicken',
    'minced beef' => 'Minced Beef',
    'ground beef' => 'Minced Beef',
    'beef' => 'Beef',
    'lamb' => 'Lamb',
    'pork' => 'Pork',
    'salmon' => 'Salmon',
    'tuna' => 'Tuna',
    'shrimp' => 'Shrimp',
    'prawn' => 'Prawns',
    'prawns' => 'Prawns',
    'egg' => 'Eggs',
    'eggs' => 'Eggs',
    'milk' => 'Milk',
    'cheese' => 'Cheese',
    'rice' => 'Rice',
    'pasta' => 'Pasta',
    'bread' => 'Bread',
    'flour' => 'Flour',
    'sugar' => 'Sugar',
    'butter' => 'Butter',
    'olive oil' => 'Olive Oil',
    'oil' => 'Olive Oil',
    'lettuce' => 'Lettuce',
    'cucumber' => 'Cucumber',
    'sweet corn' => 'Sweetcorn',
    'corn' => 'Sweetcorn',
    'mushroom' => 'Mushrooms',
    'mushrooms' => 'Mushrooms',
    'broccoli' => 'Broccoli',
    'spinach' => 'Spinach',
    'peas' => 'Peas',
    'bean' => 'Beans',
    'beans' => 'Beans',
    'avocado' => 'Avocado',
    'strawberry' => 'Strawberries',
    'strawberries' => 'Strawberries',
    'blueberry' => 'Blueberries',
    'blueberries' => 'Blueberries'
  ];

  foreach ($aliases as $needle => $ingredient) {
    if (preg_match('/\b' . preg_quote($needle, '/') . '\b/', $value)) {
      return $ingredient;
    }
  }

  $words = array_filter(explode(' ', $value), fn($word) => !in_array($word, ['fresh', 'organic', 'frozen', 'pack', 'bag', 'box', 'kg', 'g', 'lb', 'large', 'small'], true));
  $fallback = implode(' ', $words);
  $fallback = $fallback !== '' ? $fallback : 'Chicken';

  return ucwords(substr($fallback, 0, 60));
}
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
  <div class="app" data-view="dashboard" data-page="store">
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
          <a class="nav-link active" href="store.php">Store</a>
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
            <span class="page-kicker">Smart store</span>
            <h2>Shop ingredients around your goals.</h2>
            <p>Filter products, build a quick cart, and keep your meal planning tied to real stock.</p>
          </div>
          <div class="page-head-actions">
            <a class="btn ghost" href="#cart">View cart</a>
            <a class="btn ghost" href="logout.php">Log out</a>
          </div>
        </header>

        <div class="insight-row store-insights" aria-label="Store overview">
          <div class="insight-card">
            <span>Products</span>
            <strong><?= count($products) ?></strong>
            <small>Available today</small>
          </div>
          <div class="insight-card">
            <span>Categories</span>
            <strong><?= count($categories) ?></strong>
            <small>Ways to filter</small>
          </div>
          <div class="insight-card">
            <span>Cart</span>
            <strong id="cart-insight-count">0</strong>
            <small>Items selected</small>
          </div>
        </div>

        <div class="store-layout">
          <section class="card store-main-card">
            <div class="store-toolbar">
              <div>
                <h3>Available products</h3>
                <p class="muted">Filter by category and add items to your shopping cart.</p>
              </div>
              <div class="store-tools">
                <label class="store-search">
                  <span>Search products</span>
                  <input id="product-search" type="search" placeholder="Search ingredients, protein, snacks..." />
                </label>
                <div class="store-filters" aria-label="Product category filters">
                  <button class="filter-chip active" type="button" data-filter="all">All</button>
                  <?php foreach ($categories as $category): ?>
                    <button class="filter-chip" type="button" data-filter="<?= e($category['id']) ?>"><?= e($category['name']) ?></button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div id="product-list" class="product-grid">
              <?php if (!$products): ?>
                <p class="muted">No products available today.</p>
              <?php else: ?>
                <?php foreach ($products as $p): ?>
                  <?php $productImageUrl = product_image_url($p); ?>
                  <article
                    class="product-card"
                    data-product-id="<?= e($p['id']) ?>"
                    data-category-id="<?= e($p['category_id'] ?? '') ?>"
                    data-name="<?= e($p['name']) ?>"
                    data-description="<?= e($p['description']) ?>"
                    data-price="<?= e($p['price']) ?>"
                    data-stock="<?= e($p['stock']) ?>"
                  >
                    <div class="product-thumb">
                      <img src="<?= e($productImageUrl) ?>" alt="<?= e($p['name']) ?>" loading="lazy" />
                    </div>
                    <div class="product-card-body">
                      <div class="category-chip-row">
                        <span><?= e($p['category_name'] ?? 'Uncategorized') ?></span>
                      </div>
                      <h4><?= e($p['name']) ?></h4>
                      <p><?= e($p['description']) ?></p>
                      <div class="meta">
                        <span>$<?= e(number_format((float) $p['price'], 2)) ?></span>
                        <span>Stock: <?= e($p['stock']) ?></span>
                      </div>
                      <button class="icon-btn add-cart-btn" type="button" <?= (int) $p['stock'] <= 0 ? 'disabled' : '' ?>>
                        <?= (int) $p['stock'] <= 0 ? 'Out of stock' : 'Add to cart' ?>
                      </button>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <p class="muted store-empty" id="empty-filter" hidden></p>
          </section>

          <aside class="store-side">
            <section class="card cart-card" id="cart">
              <div class="store-card-head">
                <h3>Shopping cart</h3>
                <span id="cart-count">0 items</span>
              </div>
              <div class="cart-list" id="cart-list">
                <p class="muted">Your cart is empty.</p>
              </div>
              <div class="cart-total">
                <span>Total</span>
                <strong id="cart-total">$0.00</strong>
              </div>
              <button class="icon-btn danger" id="clear-cart" type="button">Clear cart</button>
            </section>

            <section class="card review-card">
              <div class="store-card-head">
                <h3>Customer reviews</h3>
                <span id="review-count">0</span>
              </div>
              <form class="review-form" id="review-form" novalidate>
                <input type="text" id="review-name" placeholder="Your name" required minlength="2" maxlength="80" />
                <textarea id="review-message" placeholder="Write your review" required minlength="4" maxlength="500"></textarea>
                <div class="star-rating" id="review-rating" role="radiogroup" aria-label="Rating">
                  <button type="button" class="star-button" data-rating="1" role="radio" aria-checked="false" aria-label="1 star">&#9733;</button>
                  <button type="button" class="star-button" data-rating="2" role="radio" aria-checked="false" aria-label="2 stars">&#9733;</button>
                  <button type="button" class="star-button" data-rating="3" role="radio" aria-checked="false" aria-label="3 stars">&#9733;</button>
                  <button type="button" class="star-button" data-rating="4" role="radio" aria-checked="false" aria-label="4 stars">&#9733;</button>
                  <button type="button" class="star-button" data-rating="5" role="radio" aria-checked="false" aria-label="5 stars">&#9733;</button>
                </div>
                <button class="icon-btn" type="submit">Add review</button>
                <small class="error" id="review-error"></small>
              </form>
              <div class="review-list" id="review-list"></div>
            </section>
          </aside>
        </div>
        <?php include __DIR__ . '/user_support_footer.php'; ?>
      </section>
    </main>
  </div>

  <script>
    const filterButtons = Array.from(document.querySelectorAll('.filter-chip'));
    const productCards = Array.from(document.querySelectorAll('.product-card'));
    const productSearch = document.getElementById('product-search');
    const emptyFilter = document.getElementById('empty-filter');
    const cartList = document.getElementById('cart-list');
    const cartTotal = document.getElementById('cart-total');
    const cartCount = document.getElementById('cart-count');
    const cartInsightCount = document.getElementById('cart-insight-count');
    const clearCart = document.getElementById('clear-cart');
    const reviewForm = document.getElementById('review-form');
    const reviewName = document.getElementById('review-name');
    const reviewMessage = document.getElementById('review-message');
    const reviewRating = document.getElementById('review-rating');
    const reviewError = document.getElementById('review-error');
    const reviewList = document.getElementById('review-list');
    const reviewCount = document.getElementById('review-count');
    const starButtons = Array.from(reviewRating.querySelectorAll('.star-button'));
    const productImages = Array.from(document.querySelectorAll('.product-thumb img'));
    let cart = [];
    let reviews = [];
    let selectedRating = 0;
    let activeFilter = 'all';

    try {
      reviews = JSON.parse(localStorage.getItem('nutribudgetReviews') || '[]');
    } catch (error) {
      reviews = [];
    }

    try {
      cart = JSON.parse(localStorage.getItem('nutribudgetCart') || '[]');
    } catch (error) {
      cart = [];
    }

    function money(value) {
      return `$${Number(value).toFixed(2)}`;
    }

    function escapeHtml(value) {
      return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    productImages.forEach((image) => {
      image.addEventListener('error', () => {
        const card = image.closest('.product-card');
        const name = card ? card.dataset.name : 'Product';
        image.replaceWith(Object.assign(document.createElement('div'), {
          className: 'product-placeholder',
          textContent: name.slice(0, 2).toUpperCase()
        }));
      }, { once: true });
    });

    function renderCart() {
      const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
      const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
      cartCount.textContent = `${totalItems} ${totalItems === 1 ? 'item' : 'items'}`;
      if (cartInsightCount) cartInsightCount.textContent = totalItems;
      cartTotal.textContent = money(total);
      localStorage.setItem('nutribudgetCart', JSON.stringify(cart));

      if (!cart.length) {
        cartList.innerHTML = '<p class="muted">Your cart is empty.</p>';
        return;
      }

      cartList.innerHTML = cart.map((item) => `
        <div class="cart-item">
          <div>
            <strong>${escapeHtml(item.name)}</strong>
            <span>${money(item.price)} x ${item.qty}</span>
          </div>
          <div class="cart-controls">
            <button type="button" data-cart-action="minus" data-id="${item.id}">-</button>
            <button type="button" data-cart-action="plus" data-id="${item.id}">+</button>
            <button type="button" data-cart-action="remove" data-id="${item.id}">Remove</button>
          </div>
        </div>
      `).join('');
    }

    productCards.forEach((card) => {
      card.querySelector('.add-cart-btn').addEventListener('click', () => {
        const id = card.dataset.productId;
        const stock = Number(card.dataset.stock);
        const existing = cart.find((item) => item.id === id);
        if (existing) {
          if (existing.qty >= stock) return;
          existing.qty += 1;
        } else {
          cart.push({ id, name: card.dataset.name, price: Number(card.dataset.price), stock, qty: 1 });
        }
        renderCart();
      });
    });

    cartList.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-cart-action]');
      if (!button) return;
      const item = cart.find((cartItem) => cartItem.id === button.dataset.id);
      if (!item) return;

      if (button.dataset.cartAction === 'plus' && item.qty < item.stock) item.qty += 1;
      if (button.dataset.cartAction === 'minus') item.qty -= 1;
      if (button.dataset.cartAction === 'remove' || item.qty <= 0) {
        cart = cart.filter((cartItem) => cartItem.id !== button.dataset.id);
      }
      renderCart();
    });

    clearCart.addEventListener('click', () => {
      cart = [];
      renderCart();
    });

    function applyProductFilters() {
      const query = productSearch.value.trim().toLowerCase();
      let visibleCount = 0;

      productCards.forEach((card) => {
        const categoryId = String(card.dataset.categoryId || '');
        const searchable = `${card.dataset.name || ''} ${card.dataset.description || ''}`.toLowerCase();
        const matchesCategory = activeFilter === 'all' || categoryId === String(activeFilter);
        const matchesSearch = query === '' || searchable.includes(query);
        const show = matchesCategory && matchesSearch;
        card.hidden = !show;
        if (show) visibleCount += 1;
      });

      emptyFilter.textContent = visibleCount ? '' : 'No products match your filters.';
      emptyFilter.hidden = visibleCount > 0;
    }

    filterButtons.forEach((button) => {
      button.addEventListener('click', () => {
        activeFilter = button.dataset.filter;
        filterButtons.forEach((item) => item.classList.toggle('active', item === button));
        applyProductFilters();
      });
    });

    productSearch.addEventListener('input', applyProductFilters);

    function stars(rating) {
      const safeRating = Math.max(0, Math.min(5, Number(rating) || 0));
      return Array.from({ length: 5 }, (_, index) => (
        `<span class="${index < safeRating ? 'is-filled' : ''}">&#9733;</span>`
      )).join('');
    }

    function paintRating(rating) {
      starButtons.forEach((button) => {
        const isActive = Number(button.dataset.rating) <= rating;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-checked', String(Number(button.dataset.rating) === selectedRating));
      });
    }

    starButtons.forEach((button) => {
      button.addEventListener('mouseenter', () => {
        paintRating(Number(button.dataset.rating));
      });

      button.addEventListener('focus', () => {
        paintRating(Number(button.dataset.rating));
      });

      button.addEventListener('click', () => {
        selectedRating = Number(button.dataset.rating);
        paintRating(selectedRating);
        reviewError.textContent = '';
      });
    });

    reviewRating.addEventListener('mouseleave', () => {
      paintRating(selectedRating);
    });

    function renderReviews() {
      reviewCount.textContent = reviews.length;
      reviewList.innerHTML = reviews.length ? reviews.map((review) => `
        <article class="review-item">
          <div>
            <strong>${escapeHtml(review.name)}</strong>
            <span class="review-stars" aria-label="${Number(review.rating)} out of 5 stars">${stars(review.rating)}</span>
          </div>
          <p>${escapeHtml(review.message)}</p>
        </article>
      `).join('') : '<p class="muted">No reviews yet.</p>';
    }

    reviewForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const name = reviewName.value.trim();
      const message = reviewMessage.value.trim();
      const rating = selectedRating;

      if (name.length < 2 || message.length < 4 || !rating) {
        reviewError.textContent = 'Add your name, review, and rating.';
        return;
      }

      reviews.unshift({ name, message, rating });
      reviews = reviews.slice(0, 8);
      localStorage.setItem('nutribudgetReviews', JSON.stringify(reviews));
      reviewForm.reset();
      selectedRating = 0;
      paintRating(selectedRating);
      reviewError.textContent = '';
      renderReviews();
    });

    renderCart();
    renderReviews();
    applyProductFilters();
  </script>
  <script src="user-panel.js"></script>
</body>
</html>
