<?php
require_once __DIR__ . '/../config.php';

class CategoryModel
{
    public function listAll()
    {
        $db = config::getConnexion();
        return $db->query('SELECT * FROM category ORDER BY name')->fetchAll();
    }

    public function add($name)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO category (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
    }

    public function update($id, $name)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE category SET name=:name WHERE id=:id');
        $stmt->execute(['id' => $id, 'name' => $name]);
    }

    public function delete($id)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM category WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }
}
?>
