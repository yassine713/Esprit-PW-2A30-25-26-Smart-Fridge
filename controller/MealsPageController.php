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

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'analyze_meal') {
                $this->handleMealAnalysisRequest($mealController, (int) $user['id'], $profile);
                exit;
            }

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

                $ingredientError = '';
                $mealIngredients = $this->readMealIngredientRows($ingredientIds, $quantities, $availableIngredients, $ingredientError);

                if ($ingredientError !== '') {
                    $this->storeMealFlash((int) $user['id'], ['error' => $ingredientError]);
                    header('Location: ' . $redirectUrl);
                    exit;
                }

                if ($name !== '' && $type !== '') {
                    $mealId = $mealController->addMeal($user['id'], $name, $type);
                    foreach ($mealIngredients as $ingredient) {
                        $mealController->addMealIngredient($mealId, (int) $ingredient['id'], (float) $ingredient['quantity_g']);
                    }

                    $this->storeMealFlash((int) $user['id'], [
                        'success' => 'Meal saved successfully.',
                        'meal_id' => (int) $mealId,
                        'meal_name' => $name,
                        'aiPending' => true
                    ]);
                }

                header('Location: ' . $this->buildFastMealsRedirectUrl($redirectUrl));
                exit;
            }

            if ($action === 'generate_ai_meals') {
                $aiForm = $this->readAiGeneratorForm($profileBudget);
                $result = $this->generateAiMealSuggestions($availableIngredients, $profile, $aiForm);
                $this->storeAiMealState((int) $user['id'], $aiForm, $result['suggestions'], $result['error']);
                header('Location: ' . $redirectUrl);
                exit;
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

                $this->clearAiMealState((int) $user['id']);
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

            if ($action === 'delete_selected_meals') {
                $mealIds = $_POST['meal_ids'] ?? [];
                if (!is_array($mealIds)) {
                    $mealIds = [$mealIds];
                }

                $mealController->deleteMeals($mealIds, $user['id']);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        $mealFlash = $this->readMealFlash((int) $user['id']);
        $storedAiState = $this->readAiMealState((int) $user['id']);
        if ($storedAiState !== null) {
            $aiForm = array_merge($aiForm, $storedAiState['form']);
            $aiMealSuggestions = $storedAiState['suggestions'];
            $aiMealError = $storedAiState['error'];
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
            'aiForm' => $aiForm,
            'mealFlash' => $mealFlash
        ];
    }

    private function readMealIngredientRows($ingredientIds, $quantities, $availableIngredients, &$error)
    {
        $error = '';
        $ingredientMap = $this->indexIngredientsById($availableIngredients);
        $ingredientIds = array_values((array) $ingredientIds);
        $quantities = array_values((array) $quantities);
        $rowCount = max(count($ingredientIds), count($quantities));
        $rows = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $ingredientIdText = trim((string) ($ingredientIds[$index] ?? ''));
            $quantityText = trim((string) ($quantities[$index] ?? ''));

            if ($ingredientIdText === '' && $quantityText === '') {
                continue;
            }

            if (!preg_match('/^\d+$/', $ingredientIdText)
                || !isset($ingredientMap[(int) $ingredientIdText])
                || !$this->isPositiveMealQuantity($quantityText)
            ) {
                $error = 'Choose each ingredient and enter a valid quantity.';
                return [];
            }

            $ingredient = $ingredientMap[(int) $ingredientIdText];
            $rows[] = [
                'id' => (int) $ingredient['id'],
                'name' => (string) ($ingredient['name'] ?? ''),
                'quantity_g' => (float) $quantityText,
                'calories' => (float) ($ingredient['calories'] ?? 0),
                'protein' => (float) ($ingredient['protein'] ?? 0),
                'carbs' => (float) ($ingredient['carbs'] ?? 0),
                'fat' => (float) ($ingredient['fat'] ?? 0),
                'price' => (float) ($ingredient['price'] ?? 0),
                'nutrition_complete' => $this->ingredientHasNutritionData($ingredient)
            ];
        }

        if (!$rows) {
            $error = 'Please add at least one ingredient before saving this meal.';
        }

        return $rows;
    }

    private function indexIngredientsById($ingredients)
    {
        $map = [];
        foreach ($ingredients as $ingredient) {
            $id = (int) ($ingredient['id'] ?? 0);
            if ($id > 0) {
                $map[$id] = $ingredient;
            }
        }

        return $map;
    }

    private function isPositiveMealQuantity($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^\d+(\.\d+)?$/', $value)) {
            return false;
        }

        $quantity = (float) $value;
        return $quantity > 0 && $quantity <= 999999.99;
    }

    private function ingredientHasNutritionData($ingredient)
    {
        foreach (['calories', 'protein', 'carbs', 'fat', 'price'] as $field) {
            if (!array_key_exists($field, $ingredient) || $ingredient[$field] === null || $ingredient[$field] === '') {
                return false;
            }
        }

        return true;
    }

    private function getMealFlashSessionKey($userId)
    {
        return 'nutribudget_meal_flash_' . (int) $userId;
    }

    private function storeMealFlash($userId, $data)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION[$this->getMealFlashSessionKey($userId)] = array_merge((array) $data, [
            'created_at' => time()
        ]);
    }

    private function readMealFlash($userId)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }

        $key = $this->getMealFlashSessionKey($userId);
        $flash = $_SESSION[$key] ?? [];
        unset($_SESSION[$key]);

        if (!is_array($flash)) {
            return [];
        }

        $createdAt = (int) ($flash['created_at'] ?? 0);
        if ($createdAt > 0 && ($createdAt + 600) < time()) {
            return [];
        }

        unset($flash['created_at']);
        return $flash;
    }

    private function analyzeSavedMeal($meal, $ingredients, $profile, &$error)
    {
        $error = '';
        $responseText = $this->callGeminiForMealAnalysis($meal, $ingredients, $profile, $error);
        if ($responseText === '') {
            return [];
        }

        $data = $this->decodeGeminiJson($responseText);
        $analysis = $this->normalizeMealAiAnalysis($data);
        if (!$analysis) {
            $error = 'Gemini returned an invalid meal analysis.';
            return [];
        }

        return $analysis;
    }

    private function handleMealAnalysisRequest($mealController, $userId, $profile)
    {
        $mealId = (int) ($_POST['meal_id'] ?? 0);
        if ($mealId <= 0) {
            $this->respondJson([
                'success' => false,
                'message' => 'Meal saved, but AI analysis is temporarily unavailable.'
            ], 400);
        }

        $meal = $mealController->getMealForUser($mealId, $userId);
        if (!$meal) {
            $this->respondJson([
                'success' => false,
                'message' => 'Meal saved, but AI analysis is temporarily unavailable.'
            ], 404);
        }

        $ingredients = $mealController->listMealIngredientsForUser($mealId, $userId);
        if (!$ingredients) {
            $this->respondJson([
                'success' => false,
                'message' => 'Meal saved, but AI analysis is temporarily unavailable.'
            ], 422);
        }

        $error = '';
        $analysis = $this->analyzeSavedMeal($meal, $ingredients, $profile, $error);
        if (!$analysis) {
            $analysis = $this->buildEstimatedMealAnalysis($ingredients, $profile);
            $this->storeMealAnalysisIfPossible($mealController, $mealId, $userId, $analysis);
            $this->respondJson([
                'success' => true,
                'analysis' => $analysis,
                'estimated' => true
            ]);
        }

        $this->storeMealAnalysisIfPossible($mealController, $mealId, $userId, $analysis);
        $this->respondJson([
            'success' => true,
            'analysis' => $analysis
        ]);
    }

    private function storeMealAnalysisIfPossible($mealController, $mealId, $userId, $analysis)
    {
        try {
            $mealController->updateMealAiAnalysis($mealId, $userId, $analysis);
        } catch (Exception $e) {
            return;
        }
    }

    private function buildEstimatedMealAnalysis($ingredients, $profile)
    {
        $totals = [
            'protein' => 0.0,
            'carbs' => 0.0,
            'fat' => 0.0,
            'cost' => 0.0
        ];
        $names = [];

        foreach ($ingredients as $ingredient) {
            $quantityG = (float) ($ingredient['quantity_g'] ?? 0);
            if ($quantityG <= 0) {
                continue;
            }

            $ratio = $quantityG / 100.0;
            $totals['protein'] += (float) ($ingredient['protein'] ?? 0) * $ratio;
            $totals['carbs'] += (float) ($ingredient['carbs'] ?? 0) * $ratio;
            $totals['fat'] += (float) ($ingredient['fat'] ?? 0) * $ratio;
            $totals['cost'] += (float) ($ingredient['price'] ?? 0) * $ratio;
            $names[] = strtolower((string) ($ingredient['name'] ?? ''));
        }

        $goalText = strtolower((string) ($profile['goal'] ?? ''));
        $budget = (float) ($profile['budget'] ?? 0);
        $hasVegetable = preg_match('/tomato|broccoli|salad|lettuce|spinach|carrot|cucumber|pepper|vegetable/', implode(' ', $names)) === 1;
        $score = 45;
        $good = [];
        $improve = [];

        if ($totals['protein'] >= 25) {
            $score += 20;
            $good[] = 'High protein';
        } elseif ($totals['protein'] >= 12) {
            $score += 12;
            $good[] = 'Some protein';
        } else {
            $improve[] = 'Add protein';
        }

        if ($budget <= 0 || $totals['cost'] <= $budget) {
            $score += 15;
            $budgetFeedback = 'Fits';
            $good[] = 'Budget-friendly';
        } elseif ($totals['cost'] <= $budget * 1.25) {
            $score += 6;
            $budgetFeedback = 'High';
            $improve[] = 'Lower cost';
        } else {
            $budgetFeedback = 'Too expensive';
            $improve[] = 'Use cheaper items';
        }

        if ($hasVegetable) {
            $score += 10;
        } else {
            $improve[] = 'Add vegetables';
        }

        if ($totals['fat'] > 35) {
            $score -= 8;
            $improve[] = 'Reduce oil';
        }

        $goalFeedback = 'Partial';
        if ($goalText !== '') {
            if ((strpos($goalText, 'gain') !== false || strpos($goalText, 'bulk') !== false) && $totals['protein'] >= 20) {
                $score += 8;
                $goalFeedback = 'Matches';
            } elseif ((strpos($goalText, 'loss') !== false || strpos($goalText, 'cut') !== false) && $totals['fat'] <= 25) {
                $score += 8;
                $goalFeedback = 'Matches';
            }
        }

        $score = max(0, min(100, (int) round($score)));

        return [
            'score' => $score,
            'label' => $this->scoreLabelFor($score),
            'reason' => 'Estimated because Gemini unavailable.',
            'good' => array_slice(array_values(array_unique($good ?: ['Saved meal'])), 0, 2),
            'improve' => array_slice(array_values(array_unique($improve ?: ['Add variety'])), 0, 2),
            'budget' => $budgetFeedback,
            'goal' => $goalFeedback
        ];
    }

    private function respondJson($payload, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function callGeminiForMealAnalysis($meal, $ingredients, $profile, &$error)
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
                '%s %.0fg',
                $ingredient['name'],
                (float) ($ingredient['quantity_g'] ?? 0)
            );
        }, array_slice($ingredients, 0, 10));

        $prompt = implode("\n", [
            'Score this meal. Return only compact valid JSON.',
            'Keys: score,label,reason,good,improve,budget,goal.',
            'reason max 12 words. good max 2 short items. improve max 2 short items.',
            'budget enum: Fits, High, Too expensive. goal enum: Matches, Partial, No.',
            'score 0-100. No markdown.',
            'Meal: ' . trim((string) ($meal['name'] ?? '')),
            'Ingredients: ' . implode(', ', $ingredientLines),
            'User goal: ' . (trim((string) ($profile['goal'] ?? '')) ?: 'not specified'),
            'User budget: ' . number_format((float) ($profile['budget'] ?? 0), 2)
        ]);

        $schema = [
            'type' => 'object',
            'properties' => [
                'score' => ['type' => 'integer'],
                'label' => ['type' => 'string'],
                'reason' => ['type' => 'string'],
                'good' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'improve' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'budget' => [
                    'type' => 'string',
                    'enum' => ['Fits', 'High', 'Too expensive']
                ],
                'goal' => [
                    'type' => 'string',
                    'enum' => ['Matches', 'Partial', 'No']
                ]
            ],
            'required' => [
                'score',
                'label',
                'reason',
                'good',
                'improve',
                'budget',
                'goal'
            ]
        ];

        $body = json_encode([
            'contents' => [[
                'parts' => [[
                    'text' => $prompt
                ]]
            ]],
            'generationConfig' => [
                'temperature' => 0.25,
                'maxOutputTokens' => 120,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema
            ]
        ]);

        $lastMessage = 'Gemini could not analyze the meal right now.';
        foreach (array_slice($this->getGeminiModels(), 0, 1) as $model) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';
            $transportError = '';
            $response = $this->postJson($url, $body, [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey
            ], $status, $transportError, 8, 30);

            if ($response !== '' && $status >= 200 && $status < 300) {
                $data = json_decode($response, true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if ($text === '') {
                    $error = 'Gemini returned an empty response.';
                }

                return $text;
            }

            $message = 'Gemini could not analyze the meal right now.';
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

    private function normalizeMealAiAnalysis($data)
    {
        if (!is_array($data) || !isset($data['score']) || !is_numeric($data['score'])) {
            return [];
        }

        $score = max(0, min(100, (int) round((float) $data['score'])));
        $label = $this->cleanAiText($data['label'] ?? '', 50);
        if ($label === '') {
            $label = $this->scoreLabelFor($score);
        }

        return [
            'score' => $score,
            'label' => $label,
            'reason' => $this->limitWords($this->cleanAiText($data['reason'] ?? 'Estimated from ingredients.', 120), 12),
            'good' => $this->cleanAiList($data['good'] ?? [], 'Balanced', 2, 32),
            'improve' => $this->cleanAiList($data['improve'] ?? [], 'Add vegetables', 2, 32),
            'budget' => $this->normalizeEnumText($data['budget'] ?? '', ['Fits', 'High', 'Too expensive'], 'Fits'),
            'goal' => $this->normalizeEnumText($data['goal'] ?? '', ['Matches', 'Partial', 'No'], 'Partial')
        ];
    }

    private function scoreLabelFor($score)
    {
        if ($score >= 85) {
            return 'Excellent';
        }
        if ($score >= 70) {
            return 'Good';
        }
        if ($score >= 50) {
            return 'Fair';
        }

        return 'Needs improvement';
    }

    private function cleanAiList($value, $fallback, $maxItems = 2, $maxLength = 32)
    {
        $items = [];
        foreach ((array) $value as $item) {
            $item = $this->cleanAiText($item, $maxLength);
            if ($item !== '') {
                $items[] = $item;
            }

            if (count($items) >= $maxItems) {
                break;
            }
        }

        return $items ?: [$fallback];
    }

    private function limitWords($value, $maxWords)
    {
        $words = preg_split('/\s+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return '';
        }

        return implode(' ', array_slice($words, 0, $maxWords));
    }

    private function normalizeEnumText($value, $allowed, $fallback)
    {
        $value = $this->cleanAiText($value, 40);
        foreach ($allowed as $option) {
            if (strcasecmp($value, $option) === 0) {
                return $option;
            }
        }

        return $fallback;
    }

    private function cleanAiText($value, $maxLength)
    {
        if (is_array($value)) {
            $value = implode(' ', array_filter(array_map(function ($item) {
                return is_scalar($item) ? (string) $item : '';
            }, $value)));
        } elseif (!is_scalar($value) && $value !== null) {
            $value = '';
        }

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

    private function getAiMealSessionKey($userId)
    {
        return 'nutribudget_ai_meals_' . (int) $userId;
    }

    private function readAiMealState($userId)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $state = $_SESSION[$this->getAiMealSessionKey($userId)] ?? null;
        if (!is_array($state)) {
            return null;
        }

        $createdAt = (int) ($state['created_at'] ?? 0);
        if ($createdAt > 0 && ($createdAt + 3600) < time()) {
            unset($_SESSION[$this->getAiMealSessionKey($userId)]);
            return null;
        }

        $result = [
            'form' => is_array($state['form'] ?? null) ? $state['form'] : [],
            'suggestions' => $this->addAiSuggestionKeys($state['suggestions'] ?? []),
            'error' => (string) ($state['error'] ?? '')
        ];

        unset($_SESSION[$this->getAiMealSessionKey($userId)]);
        return $result;
    }

    private function storeAiMealState($userId, $aiForm, $suggestions, $error)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION[$this->getAiMealSessionKey($userId)] = [
            'form' => $aiForm,
            'suggestions' => $this->addAiSuggestionKeys($suggestions),
            'error' => (string) $error,
            'created_at' => time()
        ];
    }

    private function clearAiMealState($userId)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        unset($_SESSION[$this->getAiMealSessionKey($userId)]);
    }

    private function addAiSuggestionKeys($suggestions)
    {
        $keyed = [];
        foreach ((array) $suggestions as $suggestion) {
            if (!is_array($suggestion)) {
                continue;
            }

            $suggestion['key'] = $suggestion['key'] ?? $this->buildAiSuggestionKey($suggestion);
            $keyed[] = $suggestion;
        }

        return $keyed;
    }

    private function buildAiSuggestionKey($suggestion)
    {
        $ingredients = [];
        foreach (($suggestion['ingredients'] ?? []) as $ingredient) {
            $ingredients[] = [
                'id' => (int) ($ingredient['id'] ?? 0),
                'quantity_g' => round((float) ($ingredient['quantity_g'] ?? 0), 2)
            ];
        }

        return sha1(json_encode([
            'name' => trim((string) ($suggestion['name'] ?? '')),
            'type' => trim((string) ($suggestion['type'] ?? '')),
            'ingredients' => $ingredients
        ]));
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

        $lastMessage = 'Gemini could not generate meals right now.';
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

            $message = 'Gemini could not generate meals right now.';
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

        if (in_array((int) $status, [0, 500, 502, 503, 504], true)) {
            return true;
        }

        return strpos($text, 'high demand') !== false
            || strpos($text, 'try again later') !== false
            || strpos($text, 'overloaded') !== false
            || strpos($text, 'unavailable') !== false;
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

    private function postJson($url, $body, $headers, &$status, &$transportError = '', $connectTimeout = 8, $timeout = 45)
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
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT => $timeout
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            $transportError = curl_error($ch) ?: 'No response was received from Gemini.';
        }
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

    private function buildFastMealsRedirectUrl($url)
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? 'meals.php';
        $params = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
        }

        $params['load_videos'] = '0';

        return $path . '?' . http_build_query($params);
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
        return ($_GET['load_videos'] ?? '0') === '1';
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
