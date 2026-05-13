<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controller/auth.php';
require_once __DIR__ . '/Model/ExerciseModel.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['pending_qr_scan'] = $_SERVER['REQUEST_URI'] ?? 'scan.php';
    header('Location: view/login.php');
    exit;
}

$exerciseId = (int) ($_GET['exercise_id'] ?? 0);
$token = trim($_GET['token'] ?? '');
$user = $_SESSION['user'];
$exerciseModel = new ExerciseModel();
$exercise = $exerciseModel->verifyQrToken($exerciseId, $token);
$status = 'error';
$message = 'Invalid QR Code. Please generate a new QR Code from the exercises page.';
$durationMin = 0;

if ($exercise) {
    $durationMin = $exerciseModel->logFromQrScan($exerciseId, (int) $user['id']);
    $status = 'success';
    $message = '✅ ' . $exercise['name'] . ' logged! ' . $durationMin . ' min added for today';
}

function scan_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>NutriBudget | QR Scan</title>
  <link rel="stylesheet" href="view/styles.css" />
</head>
<body>
  <div class="app" data-view="dashboard" data-page="exercises">
    <main class="dashboard">
      <section class="content" style="min-height: 100vh; display: grid; place-items: center;">
        <div class="card exercise-card" style="max-width: 520px; width: min(520px, 92vw);">
          <h3><?= $status === 'success' ? 'Exercise logged' : 'QR scan failed' ?></h3>
          <p class="<?= $status === 'success' ? '' : 'error' ?>"><?= scan_e($message) ?></p>
          <a class="btn primary" href="view/exercises.php">Back to exercises</a>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
