<?php
require_once __DIR__ . '/ProfileC.php';

class ProfilePageController
{
    public function handle($user)
    {
        $profileController = new ProfileC();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $profileController->upsert(
                $user['id'],
                $_POST['weight'] ?? null,
                $_POST['height'] ?? null,
                trim($_POST['goal'] ?? ''),
                trim($_POST['disease'] ?? ''),
                trim($_POST['allergy'] ?? ''),
                $_POST['budget'] ?? null
            );
            header('Location: profile.php?saved=1');
            exit;
        }

        return ['profile' => $profileController->getByUserId($user['id'])];
    }
}
?>
