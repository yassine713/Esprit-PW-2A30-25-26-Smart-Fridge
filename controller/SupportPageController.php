<?php
require_once __DIR__ . '/SupportC.php';

class SupportPageController
{
    public function handle($user)
    {
        $supportController = new SupportC();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'add_request') {
                $supportController->createRequest(
                    $user['id'],
                    trim($_POST['first_name'] ?? ''),
                    trim($_POST['last_name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    trim($_POST['type'] ?? ''),
                    trim($_POST['issue_title'] ?? ''),
                    trim($_POST['description'] ?? '')
                );
                header('Location: support.php');
                exit;
            }

            if ($action === 'delete_request') {
                $supportController->deleteRequest((int) ($_POST['request_id'] ?? 0), $user['id']);
                header('Location: support.php');
                exit;
            }

            if ($action === 'update_request') {
                $supportController->updateRequest(
                    (int) ($_POST['request_id'] ?? 0),
                    $user['id'],
                    trim($_POST['first_name'] ?? ''),
                    trim($_POST['last_name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    trim($_POST['type'] ?? ''),
                    trim($_POST['issue_title'] ?? ''),
                    trim($_POST['description'] ?? '')
                );
                header('Location: support.php');
                exit;
            }
        }

        $requests = $supportController->listByUser($user['id']);
        $requestIds = array_map(fn($request) => (int) $request['id'], $requests);

        return [
            'requests' => $requests,
            'requestStats' => $supportController->getTypeStatsByUser($user['id']),
            'responsesByRequest' => $supportController->listResponsesForRequestIds($requestIds)
        ];
    }
}
?>
