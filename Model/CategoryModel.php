<?php
require_once __DIR__ . '/../config.php';

class CategoryModel
{
    public function listAll()
    {
        $db = config::getConnexion();
        return $db->query('SELECT * FROM category ORDER BY name')->fetchAll();
    }

    public function listAllWithProductCounts()
    {
        $db = config::getConnexion();
        $sql = 'SELECT c.*, COUNT(p.id) AS product_count
                FROM category c
                LEFT JOIN product p ON p.category_id = c.id
                GROUP BY c.id, c.name
                ORDER BY c.name';
        return $db->query($sql)->fetchAll();
    }

    public function add($name)
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $db = config::getConnexion();
        $exists = $db->prepare('SELECT id FROM category WHERE LOWER(name) = LOWER(:name) LIMIT 1');
        $exists->execute(['name' => $name]);
        if ($exists->fetch()) {
            return;
        }

        $stmt = $db->prepare('INSERT INTO category (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
    }

    public function update($id, $name)
    {
        $name = trim($name);
        if ($id <= 0 || $name === '') {
            return;
        }

        $db = config::getConnexion();
        $exists = $db->prepare('SELECT id FROM category WHERE LOWER(name) = LOWER(:name) AND id <> :id LIMIT 1');
        $exists->execute(['id' => $id, 'name' => $name]);
        if ($exists->fetch()) {
            return;
        }

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
