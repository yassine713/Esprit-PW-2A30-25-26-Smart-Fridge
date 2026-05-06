<?php
require_once __DIR__ . '/../config.php';

class IngredientModel
{
    public function listAll()
    {
        $db = config::getConnexion();
        return $db->query('SELECT * FROM ingredient ORDER BY name')->fetchAll();
    }

    public function add($name, $calories, $protein, $carbs, $fat, $price)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO ingredient (name, calories, protein, carbs, fat, price) VALUES (:name, :cal, :prot, :carb, :fat, :price)');
        $stmt->execute([
            'name' => $name,
            'cal' => $calories,
            'prot' => $protein,
            'carb' => $carbs,
            'fat' => $fat,
            'price' => $price
        ]);
    }

    public function update($id, $name, $calories, $protein, $carbs, $fat, $price)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE ingredient SET name=:name, calories=:cal, protein=:prot, carbs=:carb, fat=:fat, price=:price WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'cal' => $calories,
            'prot' => $protein,
            'carb' => $carbs,
            'fat' => $fat,
            'price' => $price
        ]);
    }

    public function delete($id)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM ingredient WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }
}
?>
