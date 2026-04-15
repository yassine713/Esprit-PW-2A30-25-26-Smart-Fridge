<?php
require_once __DIR__ . '/MealC.php';
require_once __DIR__ . '/SupportC.php';

class DashboardController
{
    public function load($user)
    {
        $mealController = new MealC();
        $supportController = new SupportC();

        return [
            'meals' => $mealController->listByUser($user['id']),
            'requests' => $supportController->listByUser($user['id'])
        ];
    }
}
?>
