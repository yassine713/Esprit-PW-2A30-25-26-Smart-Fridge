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

    private function validProductInput($name, $description, $price, $stock)
    {
        return strlen(trim($name)) >= 2
            && strlen(trim($name)) <= 150
            && strlen(trim($description)) >= 4
            && strlen(trim($description)) <= 1000
            && $this->validNumber($price, 999999)
            && $this->validNumber($stock, 999999, true);
    }

    private function validOptionalUrl($url)
    {
        $url = trim($url);
        return $url === '' || (strlen($url) <= 255 && filter_var($url, FILTER_VALIDATE_URL));
    }

    private function automaticProductImageUrl($name)
    {
        $ingredient = $this->normalizeProductImageName($name);
        return 'https://www.themealdb.com/images/ingredients/' . rawurlencode($ingredient) . '.png';
    }

    private function shouldUseAutomaticProductImage($imageUrl)
    {
        $imageUrl = trim($imageUrl);
        return $imageUrl === ''
            || strpos($imageUrl, 'loremflickr.com/') !== false
            || strpos($imageUrl, 'themealdb.com/images/ingredients/') !== false;
    }

    private function normalizeProductImageName($name)
    {
        $value = strtolower(trim($name));
        $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value);
        $value = trim(preg_replace('/\s+/', ' ', $value));

        $aliases = [
            'potato' => 'Potatoes',
            'potatoes' => 'Potatoes',
            'tomato' => 'Tomatoes',
            'tomatoes' => 'Tomatoes',
            'apple' => 'Apple',
            'apples' => 'Apple',
            'banana' => 'Banana',
            'bananas' => 'Banana',
            'orange' => 'Orange',
            'oranges' => 'Orange',
            'lemon' => 'Lemon',
            'lemons' => 'Lemon',
            'onion' => 'Onion',
            'onions' => 'Onion',
            'carrot' => 'Carrots',
            'carrots' => 'Carrots',
            'chicken' => 'Chicken',
            'chicken breast' => 'Chicken Breast',
            'beef' => 'Beef',
            'minced beef' => 'Minced Beef',
            'ground beef' => 'Minced Beef',
            'lamb' => 'Lamb',
            'pork' => 'Pork',
            'salmon' => 'Salmon',
            'tuna' => 'Tuna',
            'shrimp' => 'Shrimp',
            'prawn' => 'Prawns',
            'prawns' => 'Prawns',
            'egg' => 'Eggs',
            'eggs' => 'Eggs',
            'milk' => 'Milk',
            'cheese' => 'Cheese',
            'rice' => 'Rice',
            'pasta' => 'Pasta',
            'bread' => 'Bread',
            'flour' => 'Flour',
            'sugar' => 'Sugar',
            'butter' => 'Butter',
            'olive oil' => 'Olive Oil',
            'oil' => 'Olive Oil',
            'lettuce' => 'Lettuce',
            'cucumber' => 'Cucumber',
            'corn' => 'Sweetcorn',
            'sweet corn' => 'Sweetcorn',
            'mushroom' => 'Mushrooms',
            'mushrooms' => 'Mushrooms',
            'broccoli' => 'Broccoli',
            'spinach' => 'Spinach',
            'peas' => 'Peas',
            'bean' => 'Beans',
            'beans' => 'Beans',
            'avocado' => 'Avocado',
            'strawberry' => 'Strawberries',
            'strawberries' => 'Strawberries',
            'blueberry' => 'Blueberries',
            'blueberries' => 'Blueberries'
        ];

        foreach ($aliases as $needle => $ingredient) {
            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/', $value)) {
                return $ingredient;
            }
        }

        $words = array_filter(explode(' ', $value), fn($word) => !in_array($word, ['fresh', 'organic', 'frozen', 'pack', 'bag', 'box', 'kg', 'g', 'lb', 'large', 'small'], true));
        $fallback = implode(' ', $words);
        $fallback = $fallback !== '' ? $fallback : 'Chicken';

        return ucwords(substr($fallback, 0, 60));
    }

    private function validCategoryName($name)
    {
        $name = trim($name);
        return strlen($name) >= 2
            && strlen($name) <= 150
            && preg_match('/^[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ &,\'.-]*$/', $name);
    }

    private function selectedCategoryIds()
    {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if ($categoryId > 0) {
            return [$categoryId];
        }

        return array_slice($_POST['category_ids'] ?? [], 0, 1);
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
                $name = trim($_POST['c_name'] ?? '');
                if ($this->validCategoryName($name)) {
                    $categoryController->add($name);
                }
            }
            if ($action === 'update_category') {
                $name = trim($_POST['c_name'] ?? '');
                if ($this->validCategoryName($name)) {
                    $categoryController->update((int) ($_POST['category_id'] ?? 0), $name);
                }
            }
            if ($action === 'delete_category') {
                $categoryController->delete((int) ($_POST['category_id'] ?? 0));
            }
            if ($action === 'add_product') {
                $name = trim($_POST['p_name'] ?? '');
                $description = trim($_POST['p_description'] ?? '');
                $price = $_POST['p_price'] ?? 0;
                $stock = $_POST['p_stock'] ?? 0;
                $imageUrl = $this->automaticProductImageUrl($name);
                $categoryIds = $this->selectedCategoryIds();

                if ($this->validProductInput($name, $description, $price, $stock) && count($categoryIds) > 0) {
                    $productController->add(
                        $name,
                        $description,
                        $price,
                        $stock,
                        $imageUrl,
                        $categoryIds[0]
                    );
                }
            }
            if ($action === 'update_product') {
                $name = trim($_POST['p_name'] ?? '');
                $description = trim($_POST['p_description'] ?? '');
                $price = $_POST['p_price'] ?? 0;
                $stock = $_POST['p_stock'] ?? 0;
                $imageUrl = trim($_POST['p_image_url'] ?? '');
                if ($this->shouldUseAutomaticProductImage($imageUrl)) {
                    $imageUrl = $this->automaticProductImageUrl($name);
                }

                if ($this->validProductInput($name, $description, $price, $stock) && $this->validOptionalUrl($imageUrl)) {
                    $productController->update(
                        (int) ($_POST['product_id'] ?? 0),
                        $name,
                        $description,
                        $price,
                        $stock,
                        $imageUrl
                    );
                }
            }
            if ($action === 'set_product_categories') {
                $categoryIds = $this->selectedCategoryIds();
                if (count($categoryIds) > 0) {
                    $productController->setCategories((int) ($_POST['product_id'] ?? 0), $categoryIds);
                }
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
        $productCategoryNames = [];
        foreach ($products as $product) {
            $productCategories = $productController->listCategories($product['id']);
            $productCategoryIds[$product['id']] = array_map(fn($category) => $category['id'], $productCategories);
            $productCategoryNames[$product['id']] = array_map(fn($category) => $category['name'], $productCategories);
        }

        return [
            'users' => $userController->listUsers(),
            'ingredients' => $ingredientController->listAll(),
            'requests' => $supportController->listAll(),
            'exercises' => $exerciseController->listExercises(),
            'products' => $products,
            'categories' => $categoryController->listAllWithProductCounts(),
            'productCategoryIds' => $productCategoryIds,
            'productCategoryNames' => $productCategoryNames
        ];
    }
}
?>
