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
                $ingredientId = $_POST['ingredient_id'] ?? '';
                $quantity = $_POST['quantity_g'] ?? '';

                if ($name !== '' && $type !== '') {
                    $mealId = $mealController->addMeal($user['id'], $name, $type);
                    if ($ingredientId !== '' && $quantity !== '') {
                        $mealController->addMealIngredient($mealId, (int) $ingredientId, (float) $quantity);
                    }
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
        foreach ($meals as $meal) {
            $mealIngredientsMap[$meal['id']] = $mealController->listMealIngredients($meal['id']);
        }

        return [
            'ingredients' => $ingredientController->listAll(),
            'meals' => $meals,
            'mealIngredientsMap' => $mealIngredientsMap
        ];
    }
}
?>
