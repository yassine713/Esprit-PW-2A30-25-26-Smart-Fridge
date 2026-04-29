<?php
require_once __DIR__ . '/../config.php';

class MealModel
{
    public function listByUser($userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM custom_meal WHERE user_id = :uid ORDER BY id DESC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function addMeal($userId, $name, $type)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO custom_meal (user_id, name, type) VALUES (:uid, :name, :type)');
        $stmt->execute(['uid' => $userId, 'name' => $name, 'type' => $type]);
        return $db->lastInsertId();
    }

    public function updateMeal($mealId, $userId, $name, $type)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE custom_meal SET name = :name, type = :type WHERE id = :id AND user_id = :uid');
        $stmt->execute(['id' => $mealId, 'uid' => $userId, 'name' => $name, 'type' => $type]);
    }

    public function deleteMeal($mealId, $userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM custom_meal WHERE id = :id AND user_id = :uid');
        $stmt->execute(['id' => $mealId, 'uid' => $userId]);
    }

    public function addMealIngredient($mealId, $ingredientId, $quantity)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO meal_ingredient (meal_id, ingredient_id, quantity_g) VALUES (:mid, :iid, :qty)');
        $stmt->execute(['mid' => $mealId, 'iid' => $ingredientId, 'qty' => $quantity]);
    }

    public function addMealIngredientForUser($mealId, $userId, $ingredientId, $quantity)
    {
        $db = config::getConnexion();
        $sql = 'INSERT INTO meal_ingredient (meal_id, ingredient_id, quantity_g)
                SELECT :mid, :iid, :qty
                FROM custom_meal
                WHERE id = :mid AND user_id = :uid
                LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute(['mid' => $mealId, 'uid' => $userId, 'iid' => $ingredientId, 'qty' => $quantity]);
    }

    public function listMealIngredients($mealId)
    {
        $db = config::getConnexion();
        $sql = 'SELECT mi.id, mi.quantity_g, i.name FROM meal_ingredient mi JOIN ingredient i ON i.id = mi.ingredient_id WHERE mi.meal_id = :mid';
        $stmt = $db->prepare($sql);
        $stmt->execute(['mid' => $mealId]);
        return $stmt->fetchAll();
    }
}
?>
