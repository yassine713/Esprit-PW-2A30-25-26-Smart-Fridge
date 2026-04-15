<?php
require_once __DIR__ . '/../config.php';

class ProductModel
{
    public function listAll()
    {
        $db = config::getConnexion();
        return $db->query('SELECT * FROM product ORDER BY created_at DESC')->fetchAll();
    }

    public function listCategories($productId)
    {
        $db = config::getConnexion();
        $sql = 'SELECT c.id, c.name FROM product_category pc JOIN category c ON c.id = pc.category_id WHERE pc.product_id = :pid';
        $stmt = $db->prepare($sql);
        $stmt->execute(['pid' => $productId]);
        return $stmt->fetchAll();
    }

    public function setCategories($productId, $categoryIds)
    {
        $db = config::getConnexion();
        $db->prepare('DELETE FROM product_category WHERE product_id = :pid')->execute(['pid' => $productId]);
        $stmt = $db->prepare('INSERT INTO product_category (product_id, category_id) VALUES (:pid, :cid)');
        foreach ($categoryIds as $cid) {
            if ($cid === '') {
                continue;
            }
            $stmt->execute(['pid' => $productId, 'cid' => $cid]);
        }
    }

    public function add($name, $description, $price, $stock, $imageUrl = null)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO product (name, description, price, stock, image_url, created_at) VALUES (:name, :description, :price, :stock, :image_url, NOW())');
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image_url' => $imageUrl
        ]);
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
}
?>
