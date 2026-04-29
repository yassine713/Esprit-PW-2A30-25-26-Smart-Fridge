<?php
require_once __DIR__ . '/../Model/ProductModel.php';

class ProductC
{
    private $model;

    public function __construct()
    {
        $this->model = new ProductModel();
    }

    public function listAll()
    {
        return $this->model->listAll();
    }

    public function listAllForStore()
    {
        return $this->model->listAllForStore();
    }

    public function listCategories($productId)
    {
        return $this->model->listCategories($productId);
    }

    public function setCategories($productId, $categoryIds)
    {
        $this->model->setCategories($productId, $categoryIds);
    }

    public function add($name, $description, $price, $stock, $imageUrl = null, $categoryId = null)
    {
        return $this->model->add($name, $description, $price, $stock, $imageUrl, $categoryId);
    }

    public function update($id, $name, $description, $price, $stock, $imageUrl = null)
    {
        $this->model->update($id, $name, $description, $price, $stock, $imageUrl);
    }

    public function delete($id)
    {
        $this->model->delete($id);
    }
}
?>
