<?php
require_once __DIR__ . '/../config.php';

class SupportModel
{
    public function createRequest($userId, $first, $last, $email, $type, $title, $desc)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO support_request (user_id, first_name, last_name, email, type, issue_title, description, status, created_at) VALUES (:uid, :first, :last, :email, :type, :title, :desc, :status, NOW())');
        $stmt->execute([
            'uid' => $userId,
            'first' => $first,
            'last' => $last,
            'email' => $email,
            'type' => $type,
            'title' => $title,
            'desc' => $desc,
            'status' => 'pending'
        ]);
    }

    public function listByUser($userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM support_request WHERE user_id = :uid ORDER BY id DESC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function listAll()
    {
        $db = config::getConnexion();
        return $db->query('SELECT * FROM support_request ORDER BY id DESC')->fetchAll();
    }

    public function updateRequest($id, $userId, $first, $last, $email, $type, $title, $desc)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('UPDATE support_request SET first_name=:first, last_name=:last, email=:email, type=:type, issue_title=:title, description=:desc WHERE id=:id AND user_id=:uid');
        $stmt->execute([
            'id' => $id,
            'uid' => $userId,
            'first' => $first,
            'last' => $last,
            'email' => $email,
            'type' => $type,
            'title' => $title,
            'desc' => $desc
        ]);
    }

    public function deleteRequest($id, $userId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('DELETE FROM support_request WHERE id=:id AND user_id=:uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public function addResponse($requestId, $adminId, $message)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('INSERT INTO support_response (request_id, admin_id, message, responded_at) VALUES (:rid, :aid, :msg, NOW())');
        $stmt->execute(['rid' => $requestId, 'aid' => $adminId, 'msg' => $message]);
        $stmt2 = $db->prepare('UPDATE support_request SET status = :status WHERE id = :id');
        $stmt2->execute(['status' => 'resolved', 'id' => $requestId]);
    }

    public function listResponses($requestId)
    {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT * FROM support_response WHERE request_id = :rid ORDER BY responded_at DESC');
        $stmt->execute(['rid' => $requestId]);
        return $stmt->fetchAll();
    }
}
?>
