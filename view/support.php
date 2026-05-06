<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/SupportPageController.php';
require_login();

$user = $_SESSION['user'];
$supportPageController = new SupportPageController();
[
  'requests' => $requests,
  'requestStats' => $requestStats,
  'responsesByRequest' => $responsesByRequest
] = $supportPageController->handle($user);

$totalRequests = count($requests);
$resolvedRequests = count(array_filter($requests, fn($request) => ($request['status'] ?? '') === 'resolved'));
$pendingRequests = $totalRequests - $resolvedRequests;
$topType = $requestStats[0]['type'] ?? 'No requests yet';
$topTypeCount = (int) ($requestStats[0]['request_count'] ?? 0);
$typePalette = [
  'Exercise' => '#2f8f37',
  'Meal' => '#d89b2b',
  'Profile' => '#2a7dbd'
];
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
  <div class="app" data-view="dashboard" data-page="support">
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
          <a class="nav-link" href="store.php">Store</a>
          <a class="nav-link" href="profile.php">Profile</a>
          <a class="nav-link active" href="support.php">Support</a>
          <?php if (($user['role'] ?? 'user') === 'admin'): ?>
            <a class="nav-link portal-link" href="access.php?target=admin"><span class="nav-icon">AP</span>Admin Panel</a>
          <?php endif; ?>
        </nav>
      </aside>

      <section class="content">
        <header class="page-head">
          <div class="page-head-copy">
            <span class="page-kicker">Help desk</span>
            <h2>Get answers without losing momentum.</h2>
            <p>Send requests, track replies, and keep support conversations connected to your goals.</p>
          </div>
          <div class="page-head-actions">
            <a class="btn ghost" href="#support-form">New request</a>
            <a class="btn ghost" href="logout.php">Log out</a>
          </div>
        </header>

        <div class="support-page">
          <section class="support-stat-panel" aria-label="Support request statistics">
            <div class="support-stat-copy">
              <span class="support-kicker">Support statistics</span>
              <h3><?= htmlspecialchars($topType) ?></h3>
              <p>
                <?= $topTypeCount > 0
                  ? 'Your most common support topic with ' . $topTypeCount . ' request' . ($topTypeCount === 1 ? '' : 's') . '.'
                  : 'Once you start sending requests, your most common topic will show here.' ?>
              </p>
            </div>
            <div class="support-stat-strip">
              <?php if (!$requestStats): ?>
                <div class="support-strip-empty">No request activity yet.</div>
              <?php else: ?>
                <?php foreach ($requestStats as $stat): ?>
                  <?php $percent = $totalRequests > 0 ? max(12, (int) round(((int) $stat['request_count'] / $totalRequests) * 100)) : 0; ?>
                  <div class="support-strip-item" style="--support-accent: <?= htmlspecialchars($typePalette[$stat['type']] ?? '#2f8f37') ?>; --support-size: <?= $percent ?>%;">
                    <strong><?= htmlspecialchars($stat['type']) ?></strong>
                    <span><?= (int) $stat['request_count'] ?> requests</span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="support-stat-counters">
              <div class="support-counter">
                <span>Total requests</span>
                <strong><?= $totalRequests ?></strong>
              </div>
              <div class="support-counter">
                <span>Resolved</span>
                <strong><?= $resolvedRequests ?></strong>
              </div>
              <div class="support-counter">
                <span>Pending</span>
                <strong><?= $pendingRequests ?></strong>
              </div>
            </div>
          </section>

          <div class="support-columns">
            <div class="support-column">
              <div class="card support-request-card">
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

              <div class="card faq">
                <h3>Frequently Asked Questions</h3>
                <details class="faq-item" open>
                  <summary>How do I track my daily calories?</summary>
                  <p>Navigate to the Dashboard and use the Add Custom Meal feature to input your meals.</p>
                </details>
                <details class="faq-item">
                  <summary>Can I change my budget later?</summary>
                  <p>Yes. Update your budget anytime from the Profile page.</p>
                </details>
                <details class="faq-item">
                  <summary>How does the AI meal generator work?</summary>
                  <p>Enter your ingredients and the AI suggests budget-friendly meals that match your goals.</p>
                </details>
              </div>
            </div>

            <div class="support-column">
              <div class="card support-history-card">
                <h3>My Requests</h3>
                <?php if (!$requests): ?>
                  <p class="muted">No support requests yet.</p>
                <?php else: ?>
                  <div class="reclamations support-request-list">
                    <?php foreach ($requests as $r): ?>
                      <?php $responses = $responsesByRequest[(int) $r['id']] ?? []; ?>
                      <div class="reclamation support-request-item">
                        <div class="rec-main">
                          <div class="support-request-head">
                            <div>
                              <h4><?= htmlspecialchars($r['issue_title']) ?></h4>
                              <div class="meta">
                                <span class="support-type-pill"><?= htmlspecialchars($r['type']) ?></span>
                                <span><?= htmlspecialchars($r['created_at']) ?></span>
                                <span class="status <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span>
                              </div>
                            </div>
                          </div>
                          <form method="post" class="inline-edit support-request-edit" novalidate>
                            <input type="hidden" name="action" value="update_request" />
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>" />
                            <input type="text" name="issue_title" value="<?= htmlspecialchars($r['issue_title']) ?>" />
                            <textarea name="description" rows="3"><?= htmlspecialchars($r['description']) ?></textarea>
                            <div class="two-col inputs">
                              <input type="text" name="first_name" value="<?= htmlspecialchars($r['first_name']) ?>" />
                              <input type="text" name="last_name" value="<?= htmlspecialchars($r['last_name']) ?>" />
                            </div>
                            <input type="text" name="email" value="<?= htmlspecialchars($r['email']) ?>" />
                            <select name="type">
                              <option <?= $r['type'] === 'Exercise' ? 'selected' : '' ?>>Exercise</option>
                              <option <?= $r['type'] === 'Meal' ? 'selected' : '' ?>>Meal</option>
                              <option <?= $r['type'] === 'Profile' ? 'selected' : '' ?>>Profile</option>
                            </select>
                            <div class="actions">
                              <button class="icon-btn" type="submit">Save</button>
                            </div>
                          </form>

                          <?php if ($responses): ?>
                            <div class="support-response-thread">
                              <span class="support-thread-label">Replies</span>
                              <?php foreach ($responses as $response): ?>
                                <div class="support-response-item">
                                  <strong><?= htmlspecialchars($response['admin_name'] ?? 'Admin') ?></strong>
                                  <p><?= htmlspecialchars($response['message']) ?></p>
                                  <span><?= htmlspecialchars($response['responded_at']) ?></span>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
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
            </div>
          </div>
        </div>
        <?php include __DIR__ . '/user_support_footer.php'; ?>
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
  <script src="user-panel.js"></script>
</body>
</html>
