<?php
require_once __DIR__ . '/../Model/MealModel.php';

class MealC
{
    private $model;

    public function __construct()
    {
        $this->model = new MealModel();
    }

    public function listByUser($userId)
    {
        return $this->model->listByUser($userId);
    }

    public function addMeal($userId, $name, $type)
    {
        return $this->model->addMeal($userId, $name, $type);
    }

    public function updateMeal($mealId, $userId, $name, $type)
    {
        $this->model->updateMeal($mealId, $userId, $name, $type);
    }

    public function deleteMeal($mealId, $userId)
    {
        $this->model->deleteMeal($mealId, $userId);
    }

    public function addMealIngredient($mealId, $ingredientId, $quantity)
    {
        $this->model->addMealIngredient($mealId, $ingredientId, $quantity);
    }

    public function addMealIngredientForUser($mealId, $userId, $ingredientId, $quantity)
    {
        $this->model->addMealIngredientForUser($mealId, $userId, $ingredientId, $quantity);
    }

    public function listMealIngredients($mealId)
    {
        return $this->model->listMealIngredients($mealId);
    }
}
?>
