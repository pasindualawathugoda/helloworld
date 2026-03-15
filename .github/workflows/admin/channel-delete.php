<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM channels WHERE id = :id");
    $stmt->execute([':id' => $id]);
}
header('Location: channels.php');
exit;