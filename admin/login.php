<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];
        header('Location: dashboard.php');
        exit;
    }

    $error = 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="mobile-container" style="max-width:420px;padding-top:60px;">
    <div class="admin-card">
        <h2 style="margin-bottom:16px;">Admin Login</h2>
        <?php if (!empty($error)): ?>
            <p style="color:#f87171;margin-bottom:14px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <div style="margin-bottom:12px;"><input type="text" name="username" placeholder="Username" required></div>
            <div style="margin-bottom:12px;"><input type="password" name="password" placeholder="Password" required></div>
            <button type="submit">Login</button>
        </form>
    </div>
</div>
</body>
</html>