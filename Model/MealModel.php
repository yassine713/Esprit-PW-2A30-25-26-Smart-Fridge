<?php
require_once __DIR__ . '/../config.php';

class MealModel
{
    private $customMealColumns = null;

    public function listByUser($userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM custom_meal WHERE user_id = :uid ORDER BY id DESC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function getMealForUser($mealId, $userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM custom_meal WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute(['id' => (int) $mealId, 'uid' => (int) $userId]);
        return $stmt->fetch() ?: null;
    }

    public function addMeal($userId, $name, $type)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO custom_meal (user_id, name, type) VALUES (:uid, :name, :type)');
        $stmt->execute(['uid' => $userId, 'name' => $name, 'type' => $type]);
        return $db->lastInsertId();
    }

    public function updateMealAiAnalysis($mealId, $userId, $analysis)
    {
        $db = config::getConnexion();
        $analysis = is_array($analysis) ? $analysis : [];
        $optionalColumns = [
            'ai_score' => 'score',
            'ai_score_label' => 'label',
            'ai_score_reason' => 'reason',
            'ai_strengths' => 'good',
            'ai_weaknesses' => 'improve',
            'ai_recommended_changes' => 'improve',
            'ai_budget_feedback' => 'budget',
            'ai_goal_feedback' => 'goal'
        ];

        $sets = [];
        $params = [
            'id' => (int) $mealId,
            'uid' => (int) $userId
        ];

        foreach ($optionalColumns as $column => $dataKey) {
            if (!array_key_exists($dataKey, $analysis) || !$this->customMealHasColumn($column)) {
                continue;
            }

            $sets[] = '`' . $column . '` = :' . $column;
            $params[$column] = is_array($analysis[$dataKey])
                ? json_encode(array_values($analysis[$dataKey]), JSON_UNESCAPED_UNICODE)
                : $analysis[$dataKey];
        }

        if ($this->customMealHasColumn('ai_analyzed_at')) {
            $sets[] = '`ai_analyzed_at` = NOW()';
        }

        if (!$sets) {
            return;
        }

        $stmt = $db->prepare('UPDATE custom_meal SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :uid');
        $stmt->execute($params);
    }

    public function updateMeal($mealId, $userId, $name, $type)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE custom_meal SET name = :name, type = :type WHERE id = :id AND user_id = :uid');
        $stmt->execute(['id' => $mealId, 'uid' => $userId, 'name' => $name, 'type' => $type]);
    }

    public function deleteMeal($mealId, $userId)
    {
        return $this->deleteMeals([$mealId], $userId);
    }

    public function deleteMeals($mealIds, $userId)
    {
        $db = config::getConnexion();
        $mealIds = array_values(array_unique(array_filter(
            array_map('intval', (array) $mealIds),
            fn($mealId) => $mealId > 0
        )));

        if (!$mealIds) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($mealIds), '?'));

        try {
            $db->beginTransaction();

            $ownerParams = array_merge([(int) $userId], $mealIds);
            $ownedStmt = $db->prepare("SELECT id FROM custom_meal WHERE user_id = ? AND id IN ($placeholders)");
            $ownedStmt->execute($ownerParams);
            $ownedMealIds = array_map('intval', $ownedStmt->fetchAll(PDO::FETCH_COLUMN));

            if (!$ownedMealIds) {
                $db->commit();
                return 0;
            }

            $ownedPlaceholders = implode(',', array_fill(0, count($ownedMealIds), '?'));
            $ingredientStmt = $db->prepare("DELETE FROM meal_ingredient WHERE meal_id IN ($ownedPlaceholders)");
            $ingredientStmt->execute($ownedMealIds);

            $deleteParams = array_merge([(int) $userId], $ownedMealIds);
            $mealStmt = $db->prepare("DELETE FROM custom_meal WHERE user_id = ? AND id IN ($ownedPlaceholders)");
            $mealStmt->execute($deleteParams);
            $deleted = $mealStmt->rowCount();

            $db->commit();
            return $deleted;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
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
        $sql = 'SELECT mi.id, mi.quantity_g, i.name, i.calories, i.protein, i.carbs, i.fat
                FROM meal_ingredient mi
                JOIN ingredient i ON i.id = mi.ingredient_id
                WHERE mi.meal_id = :mid';
        $stmt = $db->prepare($sql);
        $stmt->execute(['mid' => $mealId]);
        return $stmt->fetchAll();
    }

    public function listMealIngredientsForUser($mealId, $userId)
    {
        $db = config::getConnexion();
        $sql = 'SELECT mi.id, mi.quantity_g, i.name, i.calories, i.protein, i.carbs, i.fat, i.price
                FROM meal_ingredient mi
                JOIN custom_meal cm ON cm.id = mi.meal_id
                JOIN ingredient i ON i.id = mi.ingredient_id
                WHERE mi.meal_id = :mid AND cm.user_id = :uid
                ORDER BY mi.id';
        $stmt = $db->prepare($sql);
        $stmt->execute(['mid' => (int) $mealId, 'uid' => (int) $userId]);
        return $stmt->fetchAll();
    }

    public function listIngredientsForMealIds($mealIds)
    {
        $mealIds = array_values(array_unique(array_filter(
            array_map('intval', (array) $mealIds),
            fn($mealId) => $mealId > 0
        )));

        $ingredientsByMeal = [];
        foreach ($mealIds as $mealId) {
            $ingredientsByMeal[$mealId] = [];
        }

        if (!$mealIds) {
            return $ingredientsByMeal;
        }

        $db = config::getConnexion();
        $placeholders = implode(',', array_fill(0, count($mealIds), '?'));
        $sql = "SELECT mi.meal_id, mi.id, mi.quantity_g, i.name, i.calories, i.protein, i.carbs, i.fat
                FROM meal_ingredient mi
                JOIN ingredient i ON i.id = mi.ingredient_id
                WHERE mi.meal_id IN ($placeholders)
                ORDER BY mi.meal_id, mi.id";
        $stmt = $db->prepare($sql);
        $stmt->execute($mealIds);

        foreach ($stmt->fetchAll() as $row) {
            $mealId = (int) $row['meal_id'];
            unset($row['meal_id']);
            $ingredientsByMeal[$mealId][] = $row;
        }

        return $ingredientsByMeal;
    }

    private function customMealHasColumn($column)
    {
        $allowedColumns = [
            'ai_score' => true,
            'ai_score_label' => true,
            'ai_score_reason' => true,
            'ai_strengths' => true,
            'ai_weaknesses' => true,
            'ai_recommended_changes' => true,
            'ai_budget_feedback' => true,
            'ai_goal_feedback' => true,
            'ai_analyzed_at' => true
        ];

        if (!isset($allowedColumns[$column])) {
            return false;
        }

        if ($this->customMealColumns === null) {
            try {
                $db = config::getConnexion();
                $stmt = $db->query('SHOW COLUMNS FROM custom_meal');
                $this->customMealColumns = [];
                foreach ($stmt->fetchAll() as $row) {
                    $this->customMealColumns[$row['Field']] = true;
                }
            } catch (Exception $e) {
                $this->customMealColumns = [];
            }
        }

        return isset($this->customMealColumns[$column]);
    }
}
?>
