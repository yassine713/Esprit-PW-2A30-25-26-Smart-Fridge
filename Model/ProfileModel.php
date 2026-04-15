<?php
require_once __DIR__ . '/../config.php';

class ProfileModel
{
    public function getByUserId($userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM profile WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch();
    }

    public function upsert($userId, $weight, $height, $goal, $disease, $allergy, $budget)
    {
        $db = config::getConnexion();
        $existing = $this->getByUserId($userId);
        if ($existing) {
            $sql = 'UPDATE profile SET weight=:weight, height=:height, goal=:goal, disease=:disease, allergy=:allergy, budget=:budget WHERE user_id=:uid';
        } else {
            $sql = 'INSERT INTO profile (user_id, weight, height, goal, disease, allergy, budget) VALUES (:uid, :weight, :height, :goal, :disease, :allergy, :budget)';
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'uid' => $userId,
            'weight' => $weight,
            'height' => $height,
            'goal' => $goal,
            'disease' => $disease,
            'allergy' => $allergy,
            'budget' => $budget
        ]);
    }
}
?>
