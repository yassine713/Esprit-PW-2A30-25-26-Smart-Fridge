<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (isset($_SESSION['user'])) {
  $destination = ($_SESSION['user']['role'] ?? 'user') === 'admin' ? 'admin/index.php' : 'dashboard.php';
  header('Location: ' . $destination);
} else {
  header('Location: login.php');
}
exit;
?>
