<?php
require_once __DIR__ . '/SupportC.php';

class SupportPageController
{
    public function handle($user)
    {
        $supportController = new SupportC();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'analyze_support_problem') {
                $error = '';
                $analysis = $supportController->analyzeProblemWithAi($_POST['problem'] ?? '', $error);
                if (!$analysis) {
                    $this->sendJson([
                        'success' => false,
                        'message' => $error !== '' ? $error : 'AI analysis is unavailable. You can still submit a normal ticket.'
                    ], 422);
                }

                $this->sendJson([
                    'success' => true,
                    'analysis' => $analysis
                ]);
            }

            if ($action === 'add_request') {
                $supportController->createRequest(
                    $user['id'],
                    trim($_POST['first_name'] ?? ''),
                    trim($_POST['last_name'] ?? ''),
                    trim($_POST['email'] ?? ''),
                    trim($_POST['type'] ?? ''),
                    trim($_POST['issue_title'] ?? ''),
                    trim($_POST['description'] ?? ''),
                    [
                        'ai_category' => $_POST['ai_category'] ?? '',
                        'ai_priority' => $_POST['ai_priority'] ?? '',
                        'ai_summary' => $_POST['ai_summary'] ?? '',
                        'ai_suggested_solution' => $_POST['ai_suggested_solution'] ?? '',
                        'ai_user_solved' => 0
                    ]
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

    private function sendJson($payload, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}
?>
