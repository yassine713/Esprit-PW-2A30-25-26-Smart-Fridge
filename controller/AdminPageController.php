<?php
require_once __DIR__ . '/UserC.php';
require_once __DIR__ . '/IngredientC.php';
require_once __DIR__ . '/SupportC.php';
require_once __DIR__ . '/ExerciseC.php';
require_once __DIR__ . '/ProductC.php';
require_once __DIR__ . '/CategoryC.php';

class AdminPageController
{
    public function handle($user)
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
                $ingredientController->add(
                    trim($_POST['name'] ?? ''),
                    $_POST['calories'] ?? 0,
                    $_POST['protein'] ?? 0,
                    $_POST['carbs'] ?? 0,
                    $_POST['fat'] ?? 0,
                    $_POST['price'] ?? 0
                );
            }
            if ($action === 'update_ingredient') {
                $ingredientController->update(
                    (int) ($_POST['ingredient_id'] ?? 0),
                    trim($_POST['name'] ?? ''),
                    $_POST['calories'] ?? 0,
                    $_POST['protein'] ?? 0,
                    $_POST['carbs'] ?? 0,
                    $_POST['fat'] ?? 0,
                    $_POST['price'] ?? 0
                );
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

            header('Location: admin.php');
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
