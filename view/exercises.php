<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/ExercisesPageController.php';
require_login();

$user = $_SESSION['user'];
$exercisesPageController = new ExercisesPageController();
[
  'exerciseList' => $exerciseList,
  'logs' => $logs
] = $exercisesPageController->handle($user);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | Exercises</title>
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
          <a class="nav-link active" href="exercises.php">Exercises</a>
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
            <h2>Exercises</h2>
            <p>Track your workouts and activity</p>
          </div>
          <a class="btn ghost" href="logout.php">Log out</a>
        </header>

        <div class="support-grid">
          <div class="card">
            <h3>Add an Exercise</h3>
            <form id="exercise-form" method="post" novalidate>
              <input type="hidden" name="action" value="add_log" />
              <label>
                <span>Exercise</span>
                <select id="ex-name" name="exercise_id">
                  <option value="">Choose exercise</option>
                  <?php foreach ($exerciseList as $ex): ?>
                    <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="error" data-error-for="ex-name"></small>
              </label>
              <label>
                <span>Duration (minutes)</span>
                <input id="ex-duration" name="duration_min" type="text" placeholder="e.g., 30" />
                <small class="error" data-error-for="ex-duration"></small>
              </label>
              <label>
                <span>Date</span>
                <input id="ex-date" name="date_done" type="text" placeholder="YYYY-MM-DD" />
                <small class="error" data-error-for="ex-date"></small>
              </label>
              <button class="btn primary" type="submit">Add Exercise</button>
            </form>
          </div>

          <div class="card">
            <h3>My Exercises</h3>
            <?php if (!$logs): ?>
              <p class="muted">No exercises yet.</p>
            <?php else: ?>
              <div class="reclamations">
                <?php foreach ($logs as $log): ?>
                  <div class="reclamation">
                    <div class="rec-main">
                      <form method="post" class="inline-edit" novalidate>
                        <input type="hidden" name="action" value="update_log" />
                        <input type="hidden" name="log_id" value="<?= $log['id'] ?>" />
                        <select name="exercise_id">
                          <?php foreach ($exerciseList as $ex): ?>
                            <option value="<?= $ex['id'] ?>" <?= $ex['name']===$log['name'] ? 'selected' : '' ?>><?= htmlspecialchars($ex['name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <input type="text" name="duration_min" value="<?= htmlspecialchars($log['duration_min']) ?>" />
                        <input type="text" name="date_done" value="<?= htmlspecialchars($log['date_done']) ?>" />
                        <div class="actions">
                          <button class="icon-btn" type="submit">Save</button>
                        </div>
                      </form>
                    </div>
                    <div class="actions">
                      <form method="post">
                        <input type="hidden" name="action" value="delete_log" />
                        <input type="hidden" name="log_id" value="<?= $log['id'] ?>" />
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
    const form = document.getElementById('exercise-form');
    const nameField = document.getElementById('ex-name');
    const durationField = document.getElementById('ex-duration');
    const dateField = document.getElementById('ex-date');

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
      if (nameField.value.trim() === '') { setError('ex-name', 'Choose an exercise.'); ok = false; } else clearError('ex-name');
      if (!/^[0-9]{1,3}$/.test(durationField.value.trim()) || Number(durationField.value) <= 0) { setError('ex-duration', 'Duration must be a positive number.'); ok = false; } else clearError('ex-duration');
      if (!/^\d{4}-\d{2}-\d{2}$/.test(dateField.value.trim())) { setError('ex-date', 'Date format must be YYYY-MM-DD.'); ok = false; } else clearError('ex-date');
      if (!ok) e.preventDefault();
    });
  </script>
</body>
</html>
