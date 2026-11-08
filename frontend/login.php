<?php
session_start();
require_once '../config/db_connect.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
       
        $stmt = $pdo->prepare("SELECT user_id, full_name, password_hash, role, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'suspended') {
                $error = "Your account has been suspended. Please contact support.";
            } else {
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];

                
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } elseif ($user['role'] === 'owner') {
                    header("Location: owner/dashboard.php"); // Assuming Member 3 builds this
                } else {
                    header("Location: index.php"); // Student/Guest home page
                }
                exit();
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - UniStay</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-container h2 { margin-top: 0; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 10px; background-color: #333; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background-color: #555; }
        .error-message { color: red; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>UniStay Login</h2>
    
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn-submit">Sign In</button>
    </form>
</div>

</body>
</html>