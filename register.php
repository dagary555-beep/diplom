<?php
session_start();
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    $errors = [];

    if (empty($login) || empty($password) || empty($fullname) || empty($phone) || empty($email)) {
        $errors[] = 'Заполните все поля';
    }
    if (!preg_match('/^[a-zA-Z0-9]{7,}$/', $login)) {
        $errors[] = 'Логин содержит латиницу и цифры, не менее 7 символов';
    }
    if (strlen($password) < 5) {
        $errors[] = 'Не менее 5 символов ';
    }
    if (!preg_match('/^[а-яА-ЯёЁ\s\-]+$/u', $fullname)) {
        $errors[] = 'ФИО содержит пробелы';
    }
    if (!preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) {
        $errors[] = 'Телефон формата 8(XXX)XXX-XX-XX';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $error = 'Логин занят';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (login, password, fullname, phone, email, role) VALUES (?,?,?,?,?,'user')");
            $stmt->execute([$login, $hash, $fullname, $phone, $email]);
            header('Location: login.php');
            exit;
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="logo"><a href="index.php">Водить.РФ</a></div>
    <nav><a href="login.php">Вход</a><a href="register.php">Регистрация</a></nav>
</header>
<main>
    <div class="form">
        <h2>Регистрация</h2>
        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="login" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <input type="text" name="fullname" placeholder="ФИО" required>
            <input type="text" name="phone" placeholder="Телефон" required>
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit">Зарегистрироваться</button>
            <a href="login.php">Уже есть аккаунт?</a>
        </form>
    </div>
</main>
<footer>Водить.РФ</footer>
</body>
</html>