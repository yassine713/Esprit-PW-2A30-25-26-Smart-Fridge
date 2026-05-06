<?php
require_once __DIR__ . '/ProductC.php';
require_once __DIR__ . '/CategoryC.php';

class StorePageController
{
    public function load()
    {
        $productController = new ProductC();
        $categoryController = new CategoryC();

        return [
            'products' => $productController->listAllForStore(),
            'categories' => $categoryController->listAll()
        ];
    }
}
?>
