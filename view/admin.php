<?php
require_once __DIR__ . '/../controller/auth.php';
require_admin();

header('Location: admin/index.php');
exit;
?>
