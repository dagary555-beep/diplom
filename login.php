<?php
session_start();
require 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'mb.php'));
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>

<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Вход</title><link rel="stylesheet" href="style.css"></head>
<body>
<header><div class="logo"><a href="index.php">Водить.РФ</a></div><nav><a href="login.php">Вход</a><a href="register.php">Регистрация</a></nav></header>
<main><div class="form">
    <h2>Вход</h2>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
        <input type="text" name="login" placeholder="Логин" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
        <a href="register.php">Нет аккаунта?</a>
    </form>
</div></main>
<footer>Водить.РФ</footer>
</body>
</html>