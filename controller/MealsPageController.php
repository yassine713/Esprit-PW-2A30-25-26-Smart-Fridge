<?php
require_once __DIR__ . '/MealC.php';
require_once __DIR__ . '/IngredientC.php';
require_once __DIR__ . '/ProfileC.php';

class MealsPageController
{
    private $videoCache = [];
    private $lastVideoError = '';

    public function handle($user)
    {
        $mealController = new MealC();
        $ingredientController = new IngredientC();
        $profileController = new ProfileC();
        $profile = $profileController->getByUserId($user['id']) ?: [];
        $availableIngredients = $ingredientController->listAll();

        $sort = $_GET['sort'] ?? '';
        $dir = strtolower($_GET['dir'] ?? 'desc');
        $dir = $dir === 'asc' ? 'asc' : 'desc';
        $redirectUrl = $this->buildMealsRedirectUrl($sort, $dir);
        $profileBudget = max(0.0, (float) ($profile['budget'] ?? 0));
        $aiMealSuggestions = [];
        $aiMealError = '';
        $aiForm = [
            'meal_type' => 'Lunch',
            'protein_goal' => 'High (30g+ protein)',
            'max_budget' => $profileBudget
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'add_meal') {
                $name = trim($_POST['meal_name'] ?? '');
                $type = trim($_POST['meal_type'] ?? '');
                $ingredientIds = $_POST['ingredient_id'] ?? [];
                $quantities = $_POST['quantity_g'] ?? [];

                if (!is_array($ingredientIds)) {
                    $ingredientIds = [$ingredientIds];
                }
                if (!is_array($quantities)) {
                    $quantities = [$quantities];
                }

                if ($name !== '' && $type !== '') {
                    $mealId = $mealController->addMeal($user['id'], $name, $type);
                    foreach ($ingredientIds as $index => $ingredientId) {
                        $quantity = $quantities[$index] ?? '';
                        if ($ingredientId !== '' && is_numeric($quantity) && (float) $quantity > 0) {
                            $mealController->addMealIngredient($mealId, (int) $ingredientId, (float) $quantity);
                        }
                    }
                }

                header('Location: ' . $redirectUrl);
                exit;
            }

            if ($action === 'generate_ai_meals') {
                $aiForm = $this->readAiGeneratorForm($profileBudget);
                $result = $this->generateAiMealSuggestions($availableIngredients, $profile, $aiForm);
                $aiMealSuggestions = $result['suggestions'];
                $aiMealError = $result['error'];
            }

            if ($action === 'add_ai_meal') {
                $name = trim($_POST['ai_meal_name'] ?? '');
                $type = trim($_POST['ai_meal_type'] ?? 'Lunch');
                $ingredientIds = $_POST['ai_ingredient_id'] ?? [];
                $quantities = $_POST['ai_quantity_g'] ?? [];

                if (!is_array($ingredientIds)) {
                    $ingredientIds = [$ingredientIds];
                }
                if (!is_array($quantities)) {
                    $quantities = [$quantities];
                }

                if ($name !== '' && $type !== '') {
                    $mealId = $mealController->addMeal($user['id'], $name, $type);
                    foreach ($ingredientIds as $index => $ingredientId) {
                        $quantity = $quantities[$index] ?? '';
                        if ($ingredientId !== '' && is_numeric($quantity) && (float) $quantity > 0) {
                            $mealController->addMealIngredient($mealId, (int) $ingredientId, (float) $quantity);
                        }
                    }
                }

                header('Location: ' . $redirectUrl);
                exit;
            }

            if ($action === 'update_meal') {
                $name = trim($_POST['meal_name'] ?? '');
                $type = trim($_POST['meal_type'] ?? '');

                if ($name !== '' && $type !== '') {
                    $mealController->updateMeal((int) ($_POST['meal_id'] ?? 0), $user['id'], $name, $type);
                }

                header('Location: ' . $redirectUrl);
                exit;
            }

            if ($action === 'add_meal_ingredient') {
                $mealId = (int) ($_POST['meal_id'] ?? 0);
                $ingredientId = $_POST['ingredient_id'] ?? '';
                $quantity = $_POST['quantity_g'] ?? '';

                if ($mealId > 0 && $ingredientId !== '' && is_numeric($quantity) && (float) $quantity > 0) {
                    $mealController->addMealIngredientForUser($mealId, $user['id'], (int) $ingredientId, (float) $quantity);
                }

                header('Location: ' . $redirectUrl);
                exit;
            }

            if ($action === 'delete_meal') {
                $mealController->deleteMeal((int) ($_POST['meal_id'] ?? 0), $user['id']);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        $meals = $mealController->listByUser($user['id']);
        $mealIds = array_map(fn($meal) => (int) $meal['id'], $meals);
        $mealIngredientsMap = $mealController->listIngredientsForMealIds($mealIds);
        $mealCoachMap = [];
        $mealProteinMap = [];
        foreach ($meals as $meal) {
            $mealId = (int) $meal['id'];
            $mealIngredientsMap[$mealId] = $mealIngredientsMap[$mealId] ?? [];
            $mealCoachMap[$mealId] = $this->buildMealCoach($meal, $mealIngredientsMap[$mealId]);
            $mealProteinMap[$mealId] = $this->calculateMealProteinG($mealIngredientsMap[$mealId]);
        }

        if ($sort === 'protein') {
            usort($meals, function ($a, $b) use ($mealProteinMap, $dir) {
                $aProt = (float) ($mealProteinMap[$a['id']] ?? 0);
                $bProt = (float) ($mealProteinMap[$b['id']] ?? 0);

                if ($aProt === $bProt) {
                    return (int) $b['id'] <=> (int) $a['id'];
                }

                return $dir === 'asc' ? ($aProt <=> $bProt) : ($bProt <=> $aProt);
            });
        }

        return [
            'ingredients' => $availableIngredients,
            'meals' => $meals,
            'mealIngredientsMap' => $mealIngredientsMap,
            'mealCoachMap' => $mealCoachMap,
            'mealProteinMap' => $mealProteinMap,
            'sort' => $sort,
            'dir' => $dir,
            'profile' => $profile,
            'profileBudget' => $profileBudget,
            'aiMealSuggestions' => $aiMealSuggestions,
            'aiMealError' => $aiMealError,
            'aiForm' => $aiForm
        ];
    }

    private function readAiGeneratorForm($profileBudget)
    {
        $maxBudget = (float) ($_POST['ai_max_budget'] ?? $profileBudget);
        if ($profileBudget > 0) {
            $maxBudget = max(0.0, min($profileBudget, $maxBudget));
        } else {
            $maxBudget = 0.0;
        }

        return [
            'meal_type' => trim($_POST['ai_meal_type'] ?? 'Lunch') ?: 'Lunch',
            'protein_goal' => trim($_POST['ai_protein_goal'] ?? 'High (30g+ protein)') ?: 'High (30g+ protein)',
            'max_budget' => $maxBudget
        ];
    }

    private function generateAiMealSuggestions($ingredients, $profile, $aiForm)
    {
        if (!$ingredients) {
            return [
                'suggestions' => [],
                'error' => 'Add ingredients from the admin panel before generating meals.'
            ];
        }

        $rawMeals = [];
        $error = '';
        $responseText = $this->callGeminiForMeals($ingredients, $profile, $aiForm, $error);

        if ($responseText !== '') {
            $data = json_decode($responseText, true);
            if (is_array($data)) {
                $rawMeals = $data['meals'] ?? $data;
            } else {
                $error = 'Gemini returned an invalid meal format.';
            }
        }

        $suggestions = $this->normalizeAiMealSuggestions($rawMeals, $ingredients, $aiForm);
        if (count($suggestions) < 3) {
            $suggestions = array_slice(array_merge(
                $suggestions,
                $this->buildFallbackMealSuggestions($ingredients, $aiForm, 3 - count($suggestions))
            ), 0, 3);
        }

        return [
            'suggestions' => $suggestions,
            'error' => $error
        ];
    }

    private function callGeminiForMeals($ingredients, $profile, $aiForm, &$error)
    {
        $apiKey = defined('GEMINI_API_KEY') && GEMINI_API_KEY !== ''
            ? GEMINI_API_KEY
            : (getenv('GEMINI_API_KEY') ?: '');

        if ($apiKey === '') {
            $error = 'Gemini API key is missing.';
            return '';
        }

        $ingredientLines = array_map(function ($ingredient) {
            return sprintf(
                'ID %s: %s, %.1f kcal, %.1fg protein, %.1fg carbs, %.1fg fat, %.2f price per 100g',
                $ingredient['id'],
                $ingredient['name'],
                (float) ($ingredient['calories'] ?? 0),
                (float) ($ingredient['protein'] ?? 0),
                (float) ($ingredient['carbs'] ?? 0),
                (float) ($ingredient['fat'] ?? 0),
                (float) ($ingredient['price'] ?? 0)
            );
        }, array_slice($ingredients, 0, 80));

        $prompt = implode("\n", [
            'Create exactly 3 budget fitness meals for NutriBudget.',
            'Use only ingredient IDs from this list. Do not invent ingredient IDs.',
            'Each meal must fit the requested max budget when possible.',
            'Use grams for quantities.',
            'Prefer simple meals that a normal user can cook.',
            'User goal: ' . trim((string) ($profile['goal'] ?? 'not specified')),
            'Health conditions: ' . trim((string) ($profile['disease'] ?? 'none')),
            'Allergies to avoid: ' . trim((string) ($profile['allergy'] ?? 'none')),
            'Meal type: ' . $aiForm['meal_type'],
            'Protein goal: ' . $aiForm['protein_goal'],
            'Max budget: ' . number_format((float) $aiForm['max_budget'], 2),
            'Available ingredients:',
            implode("\n", $ingredientLines)
        ]);

        $schema = [
            'type' => 'object',
            'properties' => [
                'meals' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'ingredients' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'ingredient_id' => ['type' => 'integer'],
                                        'quantity_g' => ['type' => 'number']
                                    ],
                                    'required' => ['ingredient_id', 'quantity_g']
                                ]
                            ]
                        ],
                        'required' => ['name', 'type', 'description', 'ingredients']
                    ]
                ]
            ],
            'required' => ['meals']
        ];

        $body = json_encode([
            'contents' => [[
                'parts' => [[
                    'text' => $prompt
                ]]
            ]],
            'generationConfig' => [
                'temperature' => 0.75,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema
            ]
        ]);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        $response = $this->postJson($url, $body, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ], $status);

        if ($response === '' || $status < 200 || $status >= 300) {
            $message = 'Gemini could not generate meals right now.';
            $data = json_decode($response, true);
            if (isset($data['error']['message'])) {
                $message = $data['error']['message'];
            }
            $error = $message;
            return '';
        }

        $data = json_decode($response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($text === '') {
            $error = 'Gemini returned an empty response.';
        }

        return $text;
    }

    private function normalizeAiMealSuggestions($rawMeals, $ingredients, $aiForm)
    {
        $ingredientMap = [];
        foreach ($ingredients as $ingredient) {
            $ingredientMap[(int) $ingredient['id']] = $ingredient;
        }

        $suggestions = [];
        foreach ((array) $rawMeals as $rawMeal) {
            if (count($suggestions) >= 3 || !is_array($rawMeal)) {
                break;
            }

            $items = [];
            foreach (($rawMeal['ingredients'] ?? []) as $rawIngredient) {
                $ingredientId = (int) ($rawIngredient['ingredient_id'] ?? 0);
                $quantityG = (float) ($rawIngredient['quantity_g'] ?? 0);
                if (!isset($ingredientMap[$ingredientId]) || $quantityG <= 0) {
                    continue;
                }

                $items[] = [
                    'id' => $ingredientId,
                    'name' => $ingredientMap[$ingredientId]['name'],
                    'quantity_g' => min(1000, max(5, $quantityG))
                ];
            }

            if (!$items) {
                continue;
            }

            $summary = $this->summarizeAiMealItems($items, $ingredientMap);
            if ((float) $aiForm['max_budget'] > 0 && $summary['cost'] > ((float) $aiForm['max_budget'] * 1.15)) {
                continue;
            }

            $suggestions[] = [
                'name' => trim((string) ($rawMeal['name'] ?? 'AI Meal')),
                'type' => trim((string) ($rawMeal['type'] ?? $aiForm['meal_type'])),
                'description' => trim((string) ($rawMeal['description'] ?? 'Generated from your available ingredients.')),
                'ingredients' => $items,
                'macros' => $summary
            ];
        }

        return $suggestions;
    }

    private function summarizeAiMealItems($items, $ingredientMap)
    {
        $summary = [
            'calories' => 0.0,
            'protein' => 0.0,
            'carbs' => 0.0,
            'fat' => 0.0,
            'cost' => 0.0
        ];

        foreach ($items as $item) {
            $ingredient = $ingredientMap[(int) $item['id']] ?? null;
            if (!$ingredient) {
                continue;
            }

            $ratio = (float) $item['quantity_g'] / 100.0;
            $summary['calories'] += (float) ($ingredient['calories'] ?? 0) * $ratio;
            $summary['protein'] += (float) ($ingredient['protein'] ?? 0) * $ratio;
            $summary['carbs'] += (float) ($ingredient['carbs'] ?? 0) * $ratio;
            $summary['fat'] += (float) ($ingredient['fat'] ?? 0) * $ratio;
            $summary['cost'] += (float) ($ingredient['price'] ?? 0) * $ratio;
        }

        return [
            'calories' => (int) round($summary['calories']),
            'protein' => round($summary['protein'], 1),
            'carbs' => round($summary['carbs'], 1),
            'fat' => round($summary['fat'], 1),
            'cost' => round($summary['cost'], 2)
        ];
    }

    private function buildFallbackMealSuggestions($ingredients, $aiForm, $needed)
    {
        $ingredientMap = [];
        foreach ($ingredients as $ingredient) {
            $ingredientMap[(int) $ingredient['id']] = $ingredient;
        }

        usort($ingredients, function ($a, $b) {
            return (float) ($b['protein'] ?? 0) <=> (float) ($a['protein'] ?? 0);
        });

        $templates = [
            ['Lean Power Bowl', [140, 120, 80]],
            ['Budget Protein Plate', [170, 90, 60]],
            ['Simple Fitness Meal', [130, 150, 70]]
        ];
        $suggestions = [];

        foreach ($templates as $index => $template) {
            if (count($suggestions) >= $needed) {
                break;
            }

            $items = [];
            foreach (array_slice($ingredients, $index, 3) as $itemIndex => $ingredient) {
                $items[] = [
                    'id' => (int) $ingredient['id'],
                    'name' => $ingredient['name'],
                    'quantity_g' => $template[1][$itemIndex] ?? 100
                ];
            }

            if (!$items) {
                continue;
            }

            $suggestions[] = [
                'name' => $template[0],
                'type' => $aiForm['meal_type'],
                'description' => 'Generated from your available ingredients.',
                'ingredients' => $items,
                'macros' => $this->summarizeAiMealItems($items, $ingredientMap)
            ];
        }

        return $suggestions;
    }

    private function postJson($url, $body, $headers, &$status)
    {
        $status = 0;
        if (!function_exists('curl_init')) {
            return '';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 18
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response !== false ? $response : '';
    }

    private function calculateMealProteinG($ingredients)
    {
        $total = 0.0;
        foreach ($ingredients as $ingredient) {
            $proteinPer100g = (float) ($ingredient['protein'] ?? 0);
            $quantityG = (float) ($ingredient['quantity_g'] ?? 0);
            if ($proteinPer100g <= 0 || $quantityG <= 0) {
                continue;
            }
            $total += $proteinPer100g * ($quantityG / 100.0);
        }

        return round($total, 1);
    }

    private function buildMealsRedirectUrl($sort, $dir)
    {
        $sort = (string) $sort;
        $dir = strtolower((string) $dir);
        $dir = $dir === 'asc' ? 'asc' : 'desc';

        if ($sort !== 'protein') {
            return 'meals.php';
        }

        return 'meals.php?' . http_build_query([
            'sort' => 'protein',
            'dir' => $dir
        ]);
    }

    private function buildMealCoach($meal, $ingredients)
    {
        $ingredientNames = array_map(function ($ingredient) {
            return $ingredient['name'];
        }, $ingredients);
        $queries = $this->buildCookingVideoQueries($meal['name'] ?? '', $ingredientNames);
        $query = $queries[0];
        $video = null;

        if ($this->shouldLookupCookingVideos()) {
            foreach ($queries as $candidateQuery) {
                $query = $candidateQuery;
                $video = $this->findCookingVideo($candidateQuery);
                if ($video) {
                    break;
                }

                if ($this->lastVideoError !== 'No embeddable cooking video was found for this meal.') {
                    break;
                }
            }
        } else {
            $this->lastVideoError = 'Cooking video lookup is loaded on request.';
        }

        $ingredientCount = count($ingredientNames);

        return [
            'query' => $query,
            'searchUrl' => 'https://www.youtube.com/results?search_query=' . urlencode($query),
            'video' => $video,
            'videoError' => $this->lastVideoError,
            'hasApiKey' => $this->hasYouTubeApiKey(),
            'badge' => $ingredientCount >= 3 ? 'Recipe ready' : 'Add more ingredients',
            'tip' => $ingredientCount >= 3
                ? 'Matched with your saved ingredients for a more accurate cooking guide.'
                : 'Add more ingredients to make the video search smarter.'
        ];
    }

    private function shouldLookupCookingVideos()
    {
        return ($_GET['load_videos'] ?? '1') !== '0';
    }

    private function buildCookingVideoQuery($mealName, $ingredientNames)
    {
        return $this->buildCookingVideoQueries($mealName, $ingredientNames)[0];
    }

    private function buildCookingVideoQueries($mealName, $ingredientNames)
    {
        $mealName = trim((string) $mealName);
        $ingredientNames = array_values(array_unique(array_filter(array_map(function ($ingredientName) {
            return trim((string) $ingredientName);
        }, array_slice($ingredientNames, 0, 5)))));
        $baseMeal = $mealName !== '' ? $mealName : 'healthy meal';
        $ingredientText = trim(implode(' ', $ingredientNames));

        if ($ingredientText === '') {
            return [
                trim('how to cook ' . $baseMeal . ' simple recipe')
            ];
        }

        $strictQuery = trim('how to cook ' . $baseMeal . ' using only ' . $ingredientText . ' simple recipe no extra ingredients');
        $potatoQuery = strtolower($baseMeal . ' ' . $ingredientText);
        if (preg_match('/\b(potato|potatoes|mashed|smashed)\b/', $potatoQuery)) {
            $strictQuery .= ' without cream milk butter cheese';
        }

        return [
            $strictQuery,
            trim('how to cook ' . $baseMeal . ' with ' . $ingredientText . ' easy recipe'),
            trim('how to cook ' . $baseMeal . ' recipe')
        ];
    }

    private function hasYouTubeApiKey()
    {
        return (defined('YOUTUBE_API_KEY') && YOUTUBE_API_KEY !== '') || (getenv('YOUTUBE_API_KEY') ?: '') !== '';
    }

    private function findCookingVideo($query)
    {
        $query = trim((string) $query);
        $this->lastVideoError = '';

        if (array_key_exists($query, $this->videoCache)) {
            $cached = $this->videoCache[$query];
            $this->lastVideoError = $cached['error'];
            return $cached['video'];
        }

        $sessionCached = $this->readCachedCookingVideo($query);
        if ($sessionCached !== null) {
            return $sessionCached;
        }

        $apiKey = defined('YOUTUBE_API_KEY') && YOUTUBE_API_KEY !== ''
            ? YOUTUBE_API_KEY
            : (getenv('YOUTUBE_API_KEY') ?: '');

        if ($apiKey === '' || $query === '') {
            $this->lastVideoError = 'YouTube API key is missing.';
            $this->cacheCookingVideo($query, null, $this->lastVideoError);
            return null;
        }

        $url = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query([
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => 1,
            'order' => 'relevance',
            'safeSearch' => 'moderate',
            'videoEmbeddable' => 'true',
            'videoCategoryId' => '26',
            'key' => $apiKey
        ]);
        $response = $this->fetchJson($url);

        if (!$response) {
            $this->lastVideoError = 'Could not reach the YouTube API from this server.';
            $this->cacheCookingVideo($query, null, $this->lastVideoError);
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            $this->lastVideoError = 'YouTube API returned an invalid response.';
            $this->cacheCookingVideo($query, null, $this->lastVideoError);
            return null;
        }

        if (isset($data['error'])) {
            $this->lastVideoError = $this->formatYouTubeApiError($data['error']);
            $this->cacheCookingVideo($query, null, $this->lastVideoError);
            return null;
        }

        $item = $data['items'][0] ?? null;
        $videoId = $item['id']['videoId'] ?? '';

        if ($videoId === '') {
            $this->lastVideoError = 'No embeddable cooking video was found for this meal.';
            $this->cacheCookingVideo($query, null, $this->lastVideoError);
            return null;
        }

        $video = [
            'id' => $videoId,
            'title' => $item['snippet']['title'] ?? 'Cooking tutorial',
            'channel' => $item['snippet']['channelTitle'] ?? 'YouTube'
        ];
        $this->cacheCookingVideo($query, $video, '');

        return $video;
    }

    private function readCachedCookingVideo($query)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $cached = $_SESSION['nutribudget_video_cache'][$query] ?? null;
        if (!is_array($cached) || ((int) ($cached['created_at'] ?? 0) + 86400) < time()) {
            return null;
        }

        $video = $cached['video'] ?? null;
        $this->lastVideoError = (string) ($cached['error'] ?? '');
        $this->videoCache[$query] = [
            'video' => $video,
            'error' => $this->lastVideoError
        ];

        return $video;
    }

    private function cacheCookingVideo($query, $video, $error)
    {
        $this->videoCache[$query] = [
            'video' => $video,
            'error' => $error
        ];

        if (session_status() !== PHP_SESSION_ACTIVE || (!$video && $error !== 'No embeddable cooking video was found for this meal.')) {
            return;
        }

        $_SESSION['nutribudget_video_cache'][$query] = [
            'video' => $video,
            'error' => $error,
            'created_at' => time()
        ];

        if (count($_SESSION['nutribudget_video_cache']) > 50) {
            $_SESSION['nutribudget_video_cache'] = array_slice(
                $_SESSION['nutribudget_video_cache'],
                -50,
                null,
                true
            );
        }
    }

    private function formatYouTubeApiError($error)
    {
        $reason = $error['errors'][0]['reason'] ?? '';
        $message = $error['message'] ?? 'YouTube API rejected the request.';

        if ($reason === 'forbidden' || $reason === 'accessNotConfigured') {
            return 'YouTube API rejected the key. Enable YouTube Data API v3 and check key restrictions.';
        }

        if ($reason === 'quotaExceeded' || $reason === 'dailyLimitExceeded') {
            return 'YouTube API quota is exhausted for today.';
        }

        if ($reason === 'keyInvalid') {
            return 'YouTube API key is invalid.';
        }

        return $message;
    }

    private function fetchJson($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $status >= 200 && $status < 300) {
                return $response;
            }
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 6,
                'ignore_errors' => true
            ]
        ]);

        return @file_get_contents($url, false, $context);
    }
}
?>
