<?php
require_once __DIR__ . '/ProductC.php';

class StorePageController
{
    public function load()
    {
        $productController = new ProductC();
        return ['products' => $productController->listAll()];
    }
}
?>
