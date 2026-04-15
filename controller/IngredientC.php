<?php
require_once __DIR__ . '/../Model/IngredientModel.php';

class IngredientC
{
    private $model;

    public function __construct()
    {
        $this->model = new IngredientModel();
    }

    public function listAll()
    {
        return $this->model->listAll();
    }

    public function add($name, $calories, $protein, $carbs, $fat, $price)
    {
        $this->model->add($name, $calories, $protein, $carbs, $fat, $price);
    }

    public function update($id, $name, $calories, $protein, $carbs, $fat, $price)
    {
        $this->model->update($id, $name, $calories, $protein, $carbs, $fat, $price);
    }

    public function delete($id)
    {
        $this->model->delete($id);
    }
}
?>
