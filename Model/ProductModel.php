<?php
require_once __DIR__ . '/../config.php';

class ProductModel
{
    public function listAll()
    {
        $db = config::getConnexion();
        $sql = 'SELECT p.*, c.name AS category_name
                FROM product p
                LEFT JOIN category c ON c.id = p.category_id
                ORDER BY p.created_at DESC';
        return $db->query($sql)->fetchAll();
    }

    public function listAllForStore()
    {
        $db = config::getConnexion();
        $sql = 'SELECT p.*, c.id AS category_id, c.name AS category_name
                FROM product p
                LEFT JOIN category c ON c.id = p.category_id
                ORDER BY p.created_at DESC';
        return $db->query($sql)->fetchAll();
    }

    public function listCategories($productId)
    {
        $db = config::getConnexion();
        $sql = 'SELECT c.id, c.name
                FROM product p
                JOIN category c ON c.id = p.category_id
                WHERE p.id = :pid';
        $stmt = $db->prepare($sql);
        $stmt->execute(['pid' => $productId]);
        return $stmt->fetchAll();
    }

    public function listCategoriesForProductIds($productIds)
    {
        $productIds = array_values(array_unique(array_filter(
            array_map('intval', (array) $productIds),
            fn($productId) => $productId > 0
        )));

        $categoriesByProduct = [];
        foreach ($productIds as $productId) {
            $categoriesByProduct[$productId] = [];
        }

        if (!$productIds) {
            return $categoriesByProduct;
        }

        $db = config::getConnexion();
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT p.id AS product_id, c.id, c.name
                FROM product p
                JOIN category c ON c.id = p.category_id
                WHERE p.id IN ($placeholders)
                ORDER BY p.id, c.name";
        $stmt = $db->prepare($sql);
        $stmt->execute($productIds);

        foreach ($stmt->fetchAll() as $row) {
            $productId = (int) $row['product_id'];
            unset($row['product_id']);
            $categoriesByProduct[$productId][] = $row;
        }

        return $categoriesByProduct;
    }

    public function setCategories($productId, $categoryIds)
    {
        if ($productId <= 0) {
            return;
        }

        $categoryIds = array_values(array_filter(
            array_map('intval', $categoryIds),
            fn($cid) => $cid > 0
        ));
        $categoryId = $categoryIds[0] ?? null;

        if (!$categoryId || !$this->categoryExists($categoryId)) {
            return;
        }

        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE product SET category_id = :cid WHERE id = :pid');
        $stmt->execute(['pid' => $productId, 'cid' => $categoryId]);
    }

    public function add($name, $description, $price, $stock, $imageUrl = null, $categoryId = null)
    {
        $db = config::getConnexion();
        $categoryId = (int) $categoryId;
        $categoryId = $categoryId > 0 && $this->categoryExists($categoryId) ? $categoryId : null;
        $stmt = $db->prepare('INSERT INTO product (name, description, price, stock, image_url, category_id, created_at) VALUES (:name, :description, :price, :stock, :image_url, :category_id, NOW())');
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image_url' => $imageUrl,
            'category_id' => $categoryId
        ]);

        return (int) $db->lastInsertId();
    }

    public function update($id, $name, $description, $price, $stock, $imageUrl = null)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE product SET name=:name, description=:description, price=:price, stock=:stock, image_url=:image_url WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image_url' => $imageUrl
        ]);
    }

    public function delete($id)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM product WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }

    private function categoryExists($categoryId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT id FROM category WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $categoryId]);
        return (bool) $stmt->fetch();
    }
}
?>
