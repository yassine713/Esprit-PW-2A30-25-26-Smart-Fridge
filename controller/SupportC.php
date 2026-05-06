<?php
require_once __DIR__ . '/../Model/SupportModel.php';

class SupportC
{
    private const AI_CATEGORIES = [
        'Login Problem',
        'Meal Recommendation Problem',
        'Budget Problem',
        'Store/Product Problem',
        'Exercise Problem',
        'Profile Problem',
        'External API Problem',
        'General Question',
        'Other'
    ];

    private const AI_PRIORITIES = ['Low', 'Medium', 'High', 'Urgent'];

    private $model;

    public function __construct()
    {
        $this->model = new SupportModel();
    }

    public function createRequest($userId, $first, $last, $email, $type, $title, $desc, $aiData = [])
    {
        $this->model->createRequest(
            $userId,
            $first,
            $last,
            $email,
            $type,
            $title,
            $desc,
            $this->normalizeAiSupportData($aiData)
        );
    }

    public function listByUser($userId)
    {
        return $this->model->listByUser($userId);
    }

    public function listAll()
    {
        return $this->model->listAll();
    }

    public function getRequestById($id)
    {
        return $this->model->getRequestById($id);
    }

    public function getTypeStatsByUser($userId)
    {
        return $this->model->getTypeStatsByUser($userId);
    }

    public function updateRequest($id, $userId, $first, $last, $email, $type, $title, $desc)
    {
        $this->model->updateRequest($id, $userId, $first, $last, $email, $type, $title, $desc);
    }

    public function deleteRequest($id, $userId)
    {
        $this->model->deleteRequest($id, $userId);
    }

    public function addResponse($requestId, $adminId, $message)
    {
        $this->model->addResponse($requestId, $adminId, $message);
    }

    public function updateResponse($responseId, $message)
    {
        $this->model->updateResponse($responseId, $message);
    }

    public function deleteResponse($responseId)
    {
        $this->model->deleteResponse($responseId);
    }

    public function listResponses($requestId)
    {
        return $this->model->listResponses($requestId);
    }

    public function listResponsesForRequestIds($requestIds)
    {
        return $this->model->listResponsesForRequestIds($requestIds);
    }

    public function analyzeProblemWithAi($problem, &$error = '')
    {
        $problem = trim((string) $problem);
        if (strlen($problem) < 10) {
            $error = 'Please describe the problem in at least 10 characters.';
            return [];
        }

        $problem = substr($problem, 0, 4000);
        $prompt = implode("\n", [
            "You are NutriBudget's AI support assistant.",
            'Analyze this user problem and return ONLY valid JSON.',
            '',
            'Allowed categories:',
            implode(', ', self::AI_CATEGORIES),
            '',
            'User problem:',
            '"' . $problem . '"',
            '',
            'Return this JSON format:',
            '{',
            '  "category": "one category from the allowed list",',
            '  "priority": "Low | Medium | High | Urgent",',
            '  "short_summary": "one sentence summary",',
            '  "suggested_solution": "clear helpful solution for the user",',
            '  "should_submit_ticket": true',
            '}',
            '',
            'Rules:',
            '- Do not include markdown.',
            '- Do not include extra text outside JSON.',
            '- If the problem may be solved by checking profile, budget, goal, meal generation, store, or exercise data, explain that.',
            '- If it sounds like a serious bug, set should_submit_ticket to true.',
            '- If it is simple guidance, set should_submit_ticket to false.'
        ]);

        $schema = [
            'type' => 'object',
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'enum' => self::AI_CATEGORIES
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => self::AI_PRIORITIES
                ],
                'short_summary' => ['type' => 'string'],
                'suggested_solution' => ['type' => 'string'],
                'should_submit_ticket' => ['type' => 'boolean']
            ],
            'required' => ['category', 'priority', 'short_summary', 'suggested_solution', 'should_submit_ticket']
        ];

        $text = $this->callGemini($prompt, [
            'temperature' => 0.35,
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema
        ], $error);

        if ($text === '') {
            return [];
        }

        $data = $this->decodeGeminiJson($text);
        if (!$data) {
            $error = 'Gemini returned an invalid support analysis.';
            return [];
        }

        return [
            'category' => $this->normalizeCategory($data['category'] ?? ''),
            'priority' => $this->normalizePriority($data['priority'] ?? ''),
            'short_summary' => $this->cleanAiText($data['short_summary'] ?? 'Support request summary unavailable.', 255),
            'suggested_solution' => $this->cleanAiText($data['suggested_solution'] ?? 'Please submit a ticket so the NutriBudget team can review this.', 1200),
            'should_submit_ticket' => filter_var($data['should_submit_ticket'] ?? true, FILTER_VALIDATE_BOOLEAN)
        ];
    }

    public function generateAdminReplyForRequest($request, &$error = '')
    {
        if (!$request || !is_array($request)) {
            $error = 'Support ticket was not found.';
            return '';
        }

        $prompt = implode("\n", [
            "You are NutriBudget's admin support assistant.",
            'Generate a short professional reply to this support ticket.',
            '',
            'Ticket message:',
            '"' . substr(trim((string) ($request['description'] ?? '')), 0, 4000) . '"',
            '',
            'AI category:',
            '"' . trim((string) ($request['ai_category'] ?? 'Not available')) . '"',
            '',
            'AI priority:',
            '"' . trim((string) ($request['ai_priority'] ?? 'Not available')) . '"',
            '',
            'AI summary:',
            '"' . trim((string) ($request['ai_summary'] ?? 'Not available')) . '"',
            '',
            'Suggested solution:',
            '"' . trim((string) ($request['ai_suggested_solution'] ?? 'Not available')) . '"',
            '',
            'Return only the reply text.',
            'Do not use markdown.',
            'Keep it friendly, clear, and concise.'
        ]);

        $reply = $this->callGemini($prompt, [
            'temperature' => 0.45
        ], $error);

        return $this->cleanAiText($reply, 1200);
    }

    public function normalizeAiSupportData($data)
    {
        if (!is_array($data)) {
            return [];
        }

        $category = $this->normalizeCategory($data['ai_category'] ?? $data['category'] ?? '');
        $priority = $this->normalizePriority($data['ai_priority'] ?? $data['priority'] ?? '');
        $summary = $this->cleanAiText($data['ai_summary'] ?? $data['short_summary'] ?? '', 255);
        $solution = $this->cleanAiText($data['ai_suggested_solution'] ?? $data['suggested_solution'] ?? '', 1200);

        if ($summary === '' && $solution === '' && $category === 'Other' && $priority === 'Medium') {
            return [];
        }

        return [
            'ai_category' => $category,
            'ai_priority' => $priority,
            'ai_summary' => $summary,
            'ai_suggested_solution' => $solution,
            'ai_user_solved' => !empty($data['ai_user_solved']) ? 1 : 0
        ];
    }

    private function normalizeCategory($category)
    {
        $category = trim((string) $category);
        return in_array($category, self::AI_CATEGORIES, true) ? $category : 'Other';
    }

    private function normalizePriority($priority)
    {
        $priority = trim((string) $priority);
        return in_array($priority, self::AI_PRIORITIES, true) ? $priority : 'Medium';
    }

    private function cleanAiText($value, $maxLength)
    {
        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);
        if (strlen($value) > $maxLength) {
            $value = rtrim(substr($value, 0, $maxLength - 3)) . '...';
        }

        return $value;
    }

    private function decodeGeminiJson($text)
    {
        $text = trim((string) $text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $data = json_decode($text, true);
        if (is_array($data)) {
            return $data;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $data = json_decode(substr($text, $start, $end - $start + 1), true);
            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    private function callGemini($prompt, $generationConfig, &$error)
    {
        $apiKey = defined('GEMINI_API_KEY') && GEMINI_API_KEY !== ''
            ? GEMINI_API_KEY
            : (getenv('GEMINI_API_KEY') ?: '');

        if ($apiKey === '') {
            $error = 'Gemini API key is missing.';
            return '';
        }

        $body = json_encode([
            'contents' => [[
                'parts' => [[
                    'text' => $prompt
                ]]
            ]],
            'generationConfig' => $generationConfig
        ]);

        $lastMessage = 'Gemini could not handle the support request right now.';
        foreach ($this->getGeminiModels() as $model) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';
            $transportError = '';
            $response = $this->postJson($url, $body, [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey
            ], $status, $transportError);

            if ($response !== '' && $status >= 200 && $status < 300) {
                $data = json_decode($response, true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if ($text === '') {
                    $error = 'Gemini returned an empty response.';
                }

                return $text;
            }

            $message = 'Gemini could not handle the support request right now.';
            $data = json_decode($response, true);
            if (isset($data['error']['message'])) {
                $message = $data['error']['message'];
            } elseif ($transportError !== '') {
                $message = 'Gemini request failed: ' . $transportError;
            } elseif ($status > 0) {
                $message = 'Gemini request failed with HTTP status ' . $status . '.';
            }

            $lastMessage = $message;
            if (!$this->shouldRetryGeminiModel($status, $message, $transportError)) {
                break;
            }
        }

        $error = $lastMessage;
        return '';
    }

    private function getGeminiModels()
    {
        $configured = defined('GEMINI_MODELS') ? GEMINI_MODELS : (getenv('GEMINI_MODELS') ?: '');
        $models = array_map(function ($model) {
            return preg_replace('#^models/#', '', trim($model));
        }, explode(',', $configured));
        $models = array_values(array_unique(array_filter($models, fn($model) => $model !== '')));

        return $models ?: ['gemini-2.5-flash', 'gemini-flash-latest', 'gemini-flash-lite-latest'];
    }

    private function shouldRetryGeminiModel($status, $message, $transportError)
    {
        $text = strtolower($message . ' ' . $transportError);
        if (strpos($text, 'quota exceeded') !== false
            || strpos($text, 'rate-limit') !== false
            || strpos($text, 'billing details') !== false
            || strpos($text, 'api key') !== false
            || strpos($text, 'permission') !== false
        ) {
            return false;
        }

        return $status === 0 || $status === 404 || $status === 429 || $status >= 500;
    }

    private function postJson($url, $body, $headers, &$status, &$transportError = '')
    {
        $status = 0;
        $transportError = '';
        if (!function_exists('curl_init')) {
            $transportError = 'PHP cURL extension is not enabled.';
            return '';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 45
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            $transportError = curl_error($ch) ?: 'No response was received from Gemini.';
        }
        curl_close($ch);

        return $response !== false ? $response : '';
    }
}
?>
