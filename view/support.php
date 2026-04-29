<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/SupportPageController.php';
require_login();

$user = $_SESSION['user'];
$supportPageController = new SupportPageController();
['requests' => $requests] = $supportPageController->handle($user);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Support</title>
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
          <a class="nav-link active" href="support.php">Support</a>
          <a class="nav-link portal-link" href="access.php?target=admin"><span class="nav-icon">AP</span>Admin Panel</a>
        </nav>
      </aside>

      <section class="content">
        <header class="page-head">
          <div>
            <h2>Support & Help</h2>
            <p>Get help or report an issue</p>
          </div>
          <a class="btn ghost" href="logout.php">Log out</a>
        </header>

        <div class="support-top">
          <div class="support-card">
            <div class="support-icon">?</div>
            <h4>FAQ</h4>
            <p>Common questions answered</p>
          </div>
          <div class="support-card">
            <div class="support-icon">Chat</div>
            <h4>Live Chat</h4>
            <p>Chat with our team</p>
          </div>
          <div class="support-card">
            <div class="support-icon">Email</div>
            <h4>Email Us</h4>
            <p>support@nutribudget.com</p>
          </div>
        </div>

        <div class="support-grid">
          <div class="card">
            <h3>Submit a Support Request</h3>
            <form method="post" id="support-form" novalidate>
              <input type="hidden" name="action" value="add_request" />
              <div class="two-col inputs">
                <label>
                  <span>First name</span>
                  <input name="first_name" id="first-name" type="text" placeholder="John" />
                  <small class="error" data-error-for="first-name"></small>
                </label>
                <label>
                  <span>Last name</span>
                  <input name="last_name" id="last-name" type="text" placeholder="Doe" />
                  <small class="error" data-error-for="last-name"></small>
                </label>
              </div>
              <label>
                <span>Email</span>
                <input name="email" id="email" type="text" placeholder="you@example.com" />
                <small class="error" data-error-for="email"></small>
              </label>
              <label>
                <span>Type</span>
                <select name="type" id="type">
                  <option value="">Choose type</option>
                  <option value="Exercise">Exercise</option>
                  <option value="Meal">Meal</option>
                  <option value="Profile">Profile</option>
                </select>
                <small class="error" data-error-for="type"></small>
              </label>
              <label>
                <span>Issue title</span>
                <input name="issue_title" id="issue-title" type="text" placeholder="Brief description of your issue" />
                <small class="error" data-error-for="issue-title"></small>
              </label>
              <label>
                <span>Description</span>
                <textarea name="description" id="issue-desc" rows="5" placeholder="Please provide as much detail as possible..."></textarea>
                <small class="error" data-error-for="issue-desc"></small>
              </label>
              <button class="btn primary" type="submit">Submit Request</button>
            </form>
          </div>

          <div class="card">
            <h3>My Reclamations</h3>
            <?php if (!$requests): ?>
              <p class="muted">No reclamations yet.</p>
            <?php else: ?>
              <div class="reclamations">
                <?php foreach ($requests as $r): ?>
                  <div class="reclamation">
                    <div class="rec-main">
                      <form method="post" class="inline-edit" novalidate>
                        <input type="hidden" name="action" value="update_request" />
                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>" />
                        <input type="text" name="issue_title" value="<?= htmlspecialchars($r['issue_title']) ?>" />
                        <textarea name="description" rows="2"><?= htmlspecialchars($r['description']) ?></textarea>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($r['first_name']) ?>" />
                        <input type="text" name="last_name" value="<?= htmlspecialchars($r['last_name']) ?>" />
                        <input type="text" name="email" value="<?= htmlspecialchars($r['email']) ?>" />
                        <select name="type">
                          <option <?= $r['type']==='Exercise'?'selected':'' ?>>Exercise</option>
                          <option <?= $r['type']==='Meal'?'selected':'' ?>>Meal</option>
                          <option <?= $r['type']==='Profile'?'selected':'' ?>>Profile</option>
                        </select>
                        <div class="meta">
                          <span><?= htmlspecialchars($r['created_at']) ?></span>
                          <span class="status <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span>
                        </div>
                        <div class="actions">
                          <button class="icon-btn" type="submit">Save</button>
                        </div>
                      </form>
                    </div>
                    <div class="actions">
                      <form method="post">
                        <input type="hidden" name="action" value="delete_request" />
                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>" />
                        <button class="icon-btn danger" type="submit">Delete</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="card faq">
            <h3>Frequently Asked Questions</h3>
            <div class="faq-item">
              <h4>How do I track my daily calories?</h4>
              <p>Navigate to the Dashboard and use the Add Custom Meal feature to input your meals.</p>
            </div>
            <div class="faq-item">
              <h4>Can I change my budget later?</h4>
              <p>Yes. Update your budget anytime from the Profile page.</p>
            </div>
            <div class="faq-item">
              <h4>How does the AI meal generator work?</h4>
              <p>Enter your ingredients and the AI suggests budget-friendly meals that match your goals.</p>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    const form = document.getElementById('support-form');
    const first = document.getElementById('first-name');
    const last = document.getElementById('last-name');
    const email = document.getElementById('email');
    const type = document.getElementById('type');
    const title = document.getElementById('issue-title');
    const desc = document.getElementById('issue-desc');

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
      const nameOk = /^[A-Za-z\u00C0-\u00FF' -]{2,40}$/.test(first.value.trim());
      const lastOk = /^[A-Za-z\u00C0-\u00FF' -]{2,40}$/.test(last.value.trim());
      const emailOk = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(email.value.trim());
      if (!nameOk) { setError('first-name', 'First name must be letters only.'); ok = false; } else clearError('first-name');
      if (!lastOk) { setError('last-name', 'Last name must be letters only.'); ok = false; } else clearError('last-name');
      if (!emailOk) { setError('email', 'Enter a valid email.'); ok = false; } else clearError('email');
      if (type.value.trim() === '') { setError('type', 'Choose a type.'); ok = false; } else clearError('type');
      if (title.value.trim().length < 4) { setError('issue-title', 'Title must be at least 4 characters.'); ok = false; } else clearError('issue-title');
      if (desc.value.trim().length < 10) { setError('issue-desc', 'Description must be at least 10 characters.'); ok = false; } else clearError('issue-desc');
      if (!ok) e.preventDefault();
    });
  </script>
</body>
</html>
