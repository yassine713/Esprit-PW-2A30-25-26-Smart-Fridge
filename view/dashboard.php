<?php
require_once __DIR__ . '/../controller/auth.php';
require_login();
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
  <div class="app" data-view="dashboard" data-page="home">
    <main class="template-dashboard">
      <section class="template-hero" aria-label="NutriBudget home">
        <img class="template-hero-media" src="assets/Home_Screen.gif" alt="NutriBudget animated fitness preview" width="1280" height="720" loading="lazy" decoding="async" fetchpriority="low" />
        <div class="template-hero-shade"></div>
        <header class="template-hero-nav">
          <a class="template-logo" href="dashboard.php" aria-label="NutriBudget dashboard">
            <span>nutribudget</span>
            <i></i><i></i><i></i>
          </a>
          <nav class="template-links" aria-label="Primary user navigation">
            <a href="meals.php">Meals</a>
            <a href="exercises.php">Exercice</a>
            <a href="store.php">Store</a>
            <a href="support.php">Support</a>
          </nav>
          <a class="template-cta" href="profile.php">Build your profil <span aria-hidden="true">&rarr;</span></a>
        </header>
        <div class="template-hero-copy">
          <h2>
            <span>From</span>
            <em>1 Dinar</em>
            <span>to your dream Body</span>
          </h2>
        </div>
        <aside class="template-hero-note">
          <a class="template-logout" href="logout.php" aria-label="Log out">
            <span aria-hidden="true">
              <svg viewBox="0 0 24 24" focusable="false">
                <path d="M10 17l5-5-5-5" />
                <path d="M15 12H3" />
                <path d="M21 19V5a2 2 0 0 0-2-2h-6" />
              </svg>
            </span>
            Log out
          </a>
        </aside>
      </section>
    </main>
  </div>
  <script src="user-panel.js"></script>
</body>
</html>
