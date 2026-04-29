<?php
require_once __DIR__ . '/UserC.php';
require_once __DIR__ . '/IngredientC.php';
require_once __DIR__ . '/SupportC.php';
require_once __DIR__ . '/ExerciseC.php';
require_once __DIR__ . '/ProductC.php';
require_once __DIR__ . '/CategoryC.php';

class AdminPageController
{
    private function validNumber($value, $max, $wholeNumber = false)
    {
        $value = trim((string) $value);
        $pattern = $wholeNumber ? '/^\d+$/' : '/^\d+(\.\d+)?$/';

        if (!preg_match($pattern, $value)) {
            return false;
        }

        $number = (float) $value;
        return $number >= 0 && $number <= $max;
    }

    private function validIngredientInput($name, $calories, $protein, $carbs, $fat, $price)
    {
        return strlen(trim($name)) >= 2
            && strlen(trim($name)) <= 80
            && $this->validNumber($calories, 5000, true)
            && $this->validNumber($protein, 1000)
            && $this->validNumber($carbs, 1000)
            && $this->validNumber($fat, 1000)
            && $this->validNumber($price, 999999);
    }

    public function handle($user, $redirectTo = 'admin.php')
    {
        $userController = new UserC();
        $ingredientController = new IngredientC();
        $supportController = new SupportC();
        $exerciseController = new ExerciseC();
        $productController = new ProductC();
        $categoryController = new CategoryC();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'set_role') {
                $userController->setRole((int) ($_POST['user_id'] ?? 0), $_POST['role'] ?? 'user');
            }
            if ($action === 'delete_user') {
                $userController->deleteUser((int) ($_POST['user_id'] ?? 0));
            }
            if ($action === 'add_ingredient') {
                $name = trim($_POST['name'] ?? '');
                $calories = $_POST['calories'] ?? '';
                $protein = $_POST['protein'] ?? '';
                $carbs = $_POST['carbs'] ?? '';
                $fat = $_POST['fat'] ?? '';
                $price = $_POST['price'] ?? '';

                if ($this->validIngredientInput($name, $calories, $protein, $carbs, $fat, $price)) {
                    $ingredientController->add($name, $calories, $protein, $carbs, $fat, $price);
                }
            }
            if ($action === 'update_ingredient') {
                $name = trim($_POST['name'] ?? '');
                $calories = $_POST['calories'] ?? '';
                $protein = $_POST['protein'] ?? '';
                $carbs = $_POST['carbs'] ?? '';
                $fat = $_POST['fat'] ?? '';
                $price = $_POST['price'] ?? '';

                if ($this->validIngredientInput($name, $calories, $protein, $carbs, $fat, $price)) {
                    $ingredientController->update(
                        (int) ($_POST['ingredient_id'] ?? 0),
                        $name,
                        $calories,
                        $protein,
                        $carbs,
                        $fat,
                        $price
                    );
                }
            }
            if ($action === 'delete_ingredient') {
                $ingredientController->delete((int) ($_POST['ingredient_id'] ?? 0));
            }
            if ($action === 'add_exercise') {
                $exerciseController->addExercise(trim($_POST['name'] ?? ''));
            }
            if ($action === 'update_exercise') {
                $exerciseController->updateExercise((int) ($_POST['exercise_id'] ?? 0), trim($_POST['name'] ?? ''));
            }
            if ($action === 'delete_exercise') {
                $exerciseController->deleteExercise((int) ($_POST['exercise_id'] ?? 0));
            }
            if ($action === 'add_category') {
                $categoryController->add(trim($_POST['c_name'] ?? ''));
            }
            if ($action === 'update_category') {
                $categoryController->update((int) ($_POST['category_id'] ?? 0), trim($_POST['c_name'] ?? ''));
            }
            if ($action === 'delete_category') {
                $categoryController->delete((int) ($_POST['category_id'] ?? 0));
            }
            if ($action === 'add_product') {
                $productController->add(
                    trim($_POST['p_name'] ?? ''),
                    trim($_POST['p_description'] ?? ''),
                    $_POST['p_price'] ?? 0,
                    $_POST['p_stock'] ?? 0,
                    trim($_POST['p_image_url'] ?? '')
                );
            }
            if ($action === 'update_product') {
                $productController->update(
                    (int) ($_POST['product_id'] ?? 0),
                    trim($_POST['p_name'] ?? ''),
                    trim($_POST['p_description'] ?? ''),
                    $_POST['p_price'] ?? 0,
                    $_POST['p_stock'] ?? 0,
                    trim($_POST['p_image_url'] ?? '')
                );
            }
            if ($action === 'set_product_categories') {
                $productController->setCategories((int) ($_POST['product_id'] ?? 0), $_POST['category_ids'] ?? []);
            }
            if ($action === 'delete_product') {
                $productController->delete((int) ($_POST['product_id'] ?? 0));
            }
            if ($action === 'add_response') {
                $supportController->addResponse((int) ($_POST['request_id'] ?? 0), $user['id'], trim($_POST['message'] ?? ''));
            }

            header('Location: ' . $redirectTo);
            exit;
        }

        $products = $productController->listAll();
        $productCategoryIds = [];
        foreach ($products as $product) {
            $productCategoryIds[$product['id']] = array_map(
                fn($category) => $category['id'],
                $productController->listCategories($product['id'])
            );
        }

        return [
            'users' => $userController->listUsers(),
            'ingredients' => $ingredientController->listAll(),
            'requests' => $supportController->listAll(),
            'exercises' => $exerciseController->listExercises(),
            'products' => $products,
            'categories' => $categoryController->listAll(),
            'productCategoryIds' => $productCategoryIds
        ];
    }
}
?>
