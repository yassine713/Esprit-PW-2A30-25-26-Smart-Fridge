<?php
require_once __DIR__ . '/MealC.php';
require_once __DIR__ . '/IngredientC.php';

class MealsPageController
{
    public function handle($user)
    {
        $mealController = new MealC();
        $ingredientController = new IngredientC();

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

                header('Location: meals.php');
                exit;
            }

            if ($action === 'update_meal') {
                $name = trim($_POST['meal_name'] ?? '');
                $type = trim($_POST['meal_type'] ?? '');

                if ($name !== '' && $type !== '') {
                    $mealController->updateMeal((int) ($_POST['meal_id'] ?? 0), $user['id'], $name, $type);
                }

                header('Location: meals.php');
                exit;
            }

            if ($action === 'add_meal_ingredient') {
                $mealId = (int) ($_POST['meal_id'] ?? 0);
                $ingredientId = $_POST['ingredient_id'] ?? '';
                $quantity = $_POST['quantity_g'] ?? '';

                if ($mealId > 0 && $ingredientId !== '' && is_numeric($quantity) && (float) $quantity > 0) {
                    $mealController->addMealIngredientForUser($mealId, $user['id'], (int) $ingredientId, (float) $quantity);
                }

                header('Location: meals.php');
                exit;
            }

            if ($action === 'delete_meal') {
                $mealController->deleteMeal((int) ($_POST['meal_id'] ?? 0), $user['id']);
                header('Location: meals.php');
                exit;
            }
        }

        $meals = $mealController->listByUser($user['id']);
        $mealIngredientsMap = [];
        $mealCoachMap = [];
        foreach ($meals as $meal) {
            $mealIngredientsMap[$meal['id']] = $mealController->listMealIngredients($meal['id']);
            $mealCoachMap[$meal['id']] = $this->buildMealCoach($meal, $mealIngredientsMap[$meal['id']]);
        }

        return [
            'ingredients' => $ingredientController->listAll(),
            'meals' => $meals,
            'mealIngredientsMap' => $mealIngredientsMap,
            'mealCoachMap' => $mealCoachMap
        ];
    }

    private function buildMealCoach($meal, $ingredients)
    {
        $ingredientNames = array_map(function ($ingredient) {
            return $ingredient['name'];
        }, $ingredients);
        $mainIngredients = array_slice($ingredientNames, 0, 4);
        $queryParts = array_filter(array_merge([$meal['name']], $mainIngredients, ['recipe']));
        $query = trim(implode(' ', $queryParts));
        $video = $this->findCookingVideo($query);
        $ingredientCount = count($ingredientNames);

        return [
            'query' => $query,
            'searchUrl' => 'https://www.youtube.com/results?search_query=' . urlencode($query),
            'video' => $video,
            'hasApiKey' => $this->hasYouTubeApiKey(),
            'badge' => $ingredientCount >= 3 ? 'Recipe ready' : 'Add more ingredients',
            'tip' => $ingredientCount >= 3
                ? 'Matched with your saved ingredients for a more accurate cooking guide.'
                : 'Add more ingredients to make the video search smarter.'
        ];
    }

    private function hasYouTubeApiKey()
    {
        return (defined('YOUTUBE_API_KEY') && YOUTUBE_API_KEY !== '') || (getenv('YOUTUBE_API_KEY') ?: '') !== '';
    }

    private function findCookingVideo($query)
    {
        $apiKey = defined('YOUTUBE_API_KEY') && YOUTUBE_API_KEY !== ''
            ? YOUTUBE_API_KEY
            : (getenv('YOUTUBE_API_KEY') ?: '');

        if ($apiKey === '' || $query === '') {
            return null;
        }

        $url = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query([
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => 1,
            'videoEmbeddable' => 'true',
            'key' => $apiKey
        ]);
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true
            ]
        ]);
        $response = @file_get_contents($url, false, $context);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);
        $item = $data['items'][0] ?? null;
        $videoId = $item['id']['videoId'] ?? '';

        if ($videoId === '') {
            return null;
        }

        return [
            'id' => $videoId,
            'title' => $item['snippet']['title'] ?? 'Cooking tutorial',
            'channel' => $item['snippet']['channelTitle'] ?? 'YouTube'
        ];
    }
}
?>
