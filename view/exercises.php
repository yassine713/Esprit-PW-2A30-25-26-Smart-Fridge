<?php
require_once __DIR__ . '/../controller/auth.php';
require_once __DIR__ . '/../controller/ExercisesPageController.php';
require_login();

$user = $_SESSION['user'];
$exercisesPageController = new ExercisesPageController();
[
  'exerciseList' => $exerciseList,
  'logs' => $logs,
  'objectives' => $objectives,
  'exerciseStats' => $exerciseStats
] = $exercisesPageController->handle($user);
$favoriteExercise = $exerciseStats['favorite'] ?? null;
$exerciseDistribution = $exerciseStats['distribution'] ?? [];
$totalExerciseMinutes = (int) ($exerciseStats['total_duration'] ?? 0);
$totalExerciseLogs = (int) ($exerciseStats['total_logs'] ?? 0);
$chartColors = ['#ffffff', '#f8e7bd', '#9ccf75', '#72c5a4', '#d89b2b', '#bfe8c4'];
$chartStops = [];
$chartStart = 0;
foreach ($exerciseDistribution as $index => $exerciseStat) {
  $logCount = (int) $exerciseStat['log_count'];
  $chartEnd = $totalExerciseLogs > 0 ? $chartStart + (($logCount / $totalExerciseLogs) * 100) : $chartStart;
  $chartColor = $chartColors[$index % count($chartColors)];
  $chartStops[] = $chartColor . ' ' . round($chartStart, 2) . '% ' . round($chartEnd, 2) . '%';
  $exerciseDistribution[$index]['chart_color'] = $chartColor;
  $exerciseDistribution[$index]['chart_percent'] = $totalExerciseLogs > 0 ? round(($logCount / $totalExerciseLogs) * 100) : 0;
  $chartStart = $chartEnd;
}
$chartStyle = $chartStops ? 'background: conic-gradient(' . implode(', ', $chartStops) . ');' : '';
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
  <div class="app" data-view="dashboard" data-page="exercises">
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
          <a class="nav-link active" href="exercises.php">Exercises</a>
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
            <span class="page-kicker">Training studio</span>
            <h2>Track workouts without breaking rhythm.</h2>
            <p>Log movement, watch your exercise mix, and turn objectives into visible progress.</p>
          </div>
          <div class="page-head-actions">
            <a class="btn ghost" href="#exercise-form">Log session</a>
            <a class="btn ghost" href="logout.php">Log out</a>
          </div>
        </header>

        <div class="insight-row exercise-insights" aria-label="Exercise overview">
          <div class="insight-card">
            <span>Total minutes</span>
            <strong><?= $totalExerciseMinutes ?></strong>
            <small>Across all logs</small>
          </div>
          <div class="insight-card">
            <span>Sessions</span>
            <strong><?= $totalExerciseLogs ?></strong>
            <small>Workout logs saved</small>
          </div>
          <div class="insight-card">
            <span>Objectives</span>
            <strong><?= count($objectives) ?></strong>
            <small><?= $favoriteExercise ? htmlspecialchars($favoriteExercise['name']) : 'Add your first goal' ?></small>
          </div>
        </div>

        <div class="exercise-page">
          <section class="exercise-smart-card" aria-label="Smart exercise statistic">
            <div class="exercise-chart-copy">
              <span class="exercise-kicker">Smart statistic</span>
              <h3>Exercise Mix</h3>
              <p>
                <?= $favoriteExercise
                  ? 'You do ' . htmlspecialchars($favoriteExercise['name']) . ' the most, with ' . (int) $favoriteExercise['log_count'] . ' logged sessions.'
                  : 'Add a few workouts and your exercise chart will appear here.' ?>
              </p>
            </div>
            <div class="exercise-pie-wrap">
              <div class="exercise-pie" style="<?= htmlspecialchars($chartStyle) ?>">
                <div class="exercise-pie-center">
                  <strong><?= $totalExerciseLogs ?></strong>
                  <span>logs</span>
                </div>
              </div>
            </div>
            <div class="exercise-chart-panel">
              <?php if (!$exerciseDistribution): ?>
                <p>No exercise data yet.</p>
              <?php else: ?>
                <div class="exercise-chart-legend">
                  <?php foreach ($exerciseDistribution as $exerciseStat): ?>
                    <div class="exercise-legend-row">
                      <span class="exercise-legend-dot" style="background: <?= htmlspecialchars($exerciseStat['chart_color']) ?>"></span>
                      <strong><?= htmlspecialchars($exerciseStat['name']) ?></strong>
                      <span><?= (int) $exerciseStat['chart_percent'] ?>%</span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <div class="exercise-chart-totals">
                <span><?= $totalExerciseMinutes ?> min</span>
                <span><?= $favoriteExercise ? htmlspecialchars($favoriteExercise['name']) : 'No top type' ?></span>
              </div>
            </div>
          </section>

          <div class="exercise-columns">
            <div class="exercise-column">
              <div class="card exercise-card">
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
                    <input id="ex-date" name="date_done" type="date" />
                    <small class="error" data-error-for="ex-date"></small>
                  </label>
                  <button class="btn primary" type="submit">Add Exercise</button>
                </form>
              </div>

              <div class="card exercise-card">
                <h3>My Exercises</h3>
                <?php if (!$logs): ?>
                  <p class="muted">No exercises yet.</p>
                <?php else: ?>
                  <div class="reclamations exercise-list">
                    <?php foreach ($logs as $log): ?>
                      <div class="reclamation exercise-log-item">
                        <div class="rec-main">
                          <form method="post" class="inline-edit exercise-log-edit" novalidate>
                            <input type="hidden" name="action" value="update_log" />
                            <input type="hidden" name="log_id" value="<?= $log['id'] ?>" />
                            <select name="exercise_id">
                              <?php foreach ($exerciseList as $ex): ?>
                                <option value="<?= $ex['id'] ?>" <?= (int) $ex['id'] === (int) $log['exercise_id'] ? 'selected' : '' ?>><?= htmlspecialchars($ex['name']) ?></option>
                              <?php endforeach; ?>
                            </select>
                            <input type="text" name="duration_min" value="<?= htmlspecialchars($log['duration_min']) ?>" />
                            <input type="date" name="date_done" value="<?= htmlspecialchars($log['date_done']) ?>" />
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

            <div class="exercise-column">
              <div class="card exercise-card">
                <h3>Add Objective</h3>
                <form id="objective-form" method="post" novalidate>
                  <input type="hidden" name="action" value="add_objective" />
                  <label>
                    <span>Title</span>
                    <input id="obj-title" name="title" type="text" placeholder="e.g., Running Goal" pattern="[A-Za-z ]{3,}" />
                    <small class="error" data-error-for="obj-title"></small>
                  </label>
                  <label>
                    <span>Exercise</span>
                    <select id="obj-exercise" name="exercise_id">
                      <option value="">Choose exercise</option>
                      <?php foreach ($exerciseList as $ex): ?>
                        <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <small class="error" data-error-for="obj-exercise"></small>
                  </label>
                  <label>
                    <span>Target duration (minutes)</span>
                    <input id="obj-target" name="target_duration_min" type="text" placeholder="e.g., 120" />
                    <small class="error" data-error-for="obj-target"></small>
                  </label>
                  <div class="input-row">
                    <label>
                      <span>Start date</span>
                      <input id="obj-start" name="start_date" type="date" />
                      <small class="error" data-error-for="obj-start"></small>
                    </label>
                    <label>
                      <span>End date</span>
                      <input id="obj-end" name="end_date" type="date" />
                      <small class="error" data-error-for="obj-end"></small>
                    </label>
                  </div>
                  <label>
                    <span>Status</span>
                    <select name="status">
                      <option value="active">Active</option>
                      <option value="completed">Completed</option>
                    </select>
                  </label>
                  <button class="btn primary" type="submit">Add Objective</button>
                </form>
              </div>

              <div class="card exercise-card">
                <h3>My Objectives</h3>
                <?php if (!$objectives): ?>
                  <p class="muted">No objectives yet.</p>
                <?php else: ?>
                  <div class="reclamations objective-list">
                    <?php foreach ($objectives as $objective): ?>
                      <?php
                        $target = max(1, (int) $objective['target_duration_min']);
                        $progress = (int) $objective['progress_min'];
                        $percent = min(100, round(($progress / $target) * 100));
                      ?>
                      <div class="reclamation objective-item">
                        <div class="rec-main">
                          <div class="objective-summary">
                            <h4><?= htmlspecialchars($objective['title']) ?></h4>
                            <p>
                              <?= htmlspecialchars($objective['exercise_name']) ?>:
                              <?= $progress ?> / <?= htmlspecialchars($objective['target_duration_min']) ?> minutes
                            </p>
                            <div class="objective-progress" aria-label="Objective progress">
                              <span style="width: <?= $percent ?>%"></span>
                            </div>
                            <div class="meta">
                              <span><?= htmlspecialchars($objective['start_date']) ?> to <?= htmlspecialchars($objective['end_date']) ?></span>
                              <span class="status <?= htmlspecialchars($objective['status']) ?>"><?= htmlspecialchars($objective['status']) ?></span>
                            </div>
                          </div>
                          <form method="post" class="inline-edit objective-edit" novalidate>
                            <input type="hidden" name="action" value="update_objective" />
                            <input type="hidden" name="objective_id" value="<?= $objective['id'] ?>" />
                            <input class="objective-title-edit" type="text" name="title" value="<?= htmlspecialchars($objective['title']) ?>" pattern="[A-Za-z ]{3,}" />
                            <select name="exercise_id">
                              <?php foreach ($exerciseList as $ex): ?>
                                <option value="<?= $ex['id'] ?>" <?= (int) $ex['id'] === (int) $objective['exercise_id'] ? 'selected' : '' ?>><?= htmlspecialchars($ex['name']) ?></option>
                              <?php endforeach; ?>
                            </select>
                            <input type="text" name="target_duration_min" value="<?= htmlspecialchars($objective['target_duration_min']) ?>" />
                            <div class="input-row">
                              <input type="date" name="start_date" value="<?= htmlspecialchars($objective['start_date']) ?>" />
                              <input type="date" name="end_date" value="<?= htmlspecialchars($objective['end_date']) ?>" />
                            </div>
                            <select name="status">
                              <option value="active" <?= $objective['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                              <option value="completed" <?= $objective['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                            <div class="actions">
                              <button class="icon-btn" type="submit">Save</button>
                            </div>
                          </form>
                        </div>
                        <div class="actions">
                          <form method="post">
                            <input type="hidden" name="action" value="delete_objective" />
                            <input type="hidden" name="objective_id" value="<?= $objective['id'] ?>" />
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
    const form = document.getElementById('exercise-form');
    const objectiveForm = document.getElementById('objective-form');
    const nameField = document.getElementById('ex-name');
    const durationField = document.getElementById('ex-duration');
    const dateField = document.getElementById('ex-date');
    const objectiveTitle = document.getElementById('obj-title');
    const objectiveExercise = document.getElementById('obj-exercise');
    const objectiveTarget = document.getElementById('obj-target');
    const objectiveStart = document.getElementById('obj-start');
    const objectiveEnd = document.getElementById('obj-end');

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
      if (dateField.value.trim() === '') { setError('ex-date', 'Choose a date.'); ok = false; } else clearError('ex-date');
      if (!ok) e.preventDefault();
    });

    objectiveForm.addEventListener('submit', (e) => {
      let ok = true;
      if (!/^[A-Za-z ]{3,}$/.test(objectiveTitle.value.trim())) { setError('obj-title', 'Title must contain letters only.'); ok = false; } else clearError('obj-title');
      if (objectiveExercise.value.trim() === '') { setError('obj-exercise', 'Choose an exercise.'); ok = false; } else clearError('obj-exercise');
      if (!/^[0-9]{1,4}$/.test(objectiveTarget.value.trim()) || Number(objectiveTarget.value) <= 0) { setError('obj-target', 'Target must be a positive number.'); ok = false; } else clearError('obj-target');
      if (objectiveStart.value.trim() === '') { setError('obj-start', 'Choose a start date.'); ok = false; } else clearError('obj-start');
      if (objectiveEnd.value.trim() === '') { setError('obj-end', 'Choose an end date.'); ok = false; } else clearError('obj-end');
      if (objectiveStart.value && objectiveEnd.value && objectiveStart.value > objectiveEnd.value) { setError('obj-end', 'End date must be after the start date.'); ok = false; }
      if (!ok) e.preventDefault();
    });

    document.querySelectorAll('.objective-edit').forEach((editForm) => {
      editForm.addEventListener('submit', (e) => {
        const titleInput = editForm.querySelector('.objective-title-edit');
        if (titleInput && !/^[A-Za-z ]{3,}$/.test(titleInput.value.trim())) {
          titleInput.setCustomValidity('Title must contain letters only.');
          titleInput.reportValidity();
          e.preventDefault();
        } else if (titleInput) {
          titleInput.setCustomValidity('');
        }
      });
    });
  </script>
  <script src="user-panel.js"></script>
</body>
</html>
