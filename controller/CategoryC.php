<?php
require_once __DIR__ . '/../Model/CategoryModel.php';

class CategoryC
{
    private $model;

    public function __construct()
    {
        $this->model = new CategoryModel();
    }

    public function listAll()
    {
        return $this->model->listAll();
    }

    public function add($name)
    {
        $this->model->add($name);
    }

    public function update($id, $name)
    {
        $this->model->update($id, $name);
    }

    public function delete($id)
    {
        $this->model->delete($id);
    }
}
?>
