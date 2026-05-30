<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone    = trim($_POST['phone']);
    $app_date = $_POST['app_date'];
    $service  = $_POST['service'];
    $payment  = $_POST['payment'];

    $today = date('Y-m-d');
    if ($app_date < $today) {
        $error = 'Дата не раньше сегодняшнего дня.';
    } elseif (!preg_match('/^[\d\(\)\-\+ ]{10,18}$/', $phone)) {
        $error = 'Введите корректный номер телефона.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO applications (user_id, phone, app_date, service, payment) VALUES (?,?,?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $phone, $app_date, $service, $payment]);
        $success = 'Заявка создана!';
        $phone = $app_date = $service = $payment = '';
    }
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новая заявка</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="logo"><a href="index.php">Водить.РФ</a></div>
        <nav>
            <?php if (($_SESSION['role'] ?? '') === 'admin') echo '<a href="admin.php">Админка</a>'; ?>
            <a href="mb.php">Мои заявки</a>
            <a href="create.php">Новая заявка</a>
            <a href="logout.php">Выйти</a>
        </nav>
    </header>
    <main>
        <div class="form">
            <h2>Новая заявка</h2>
            <?php if ($success): ?>
                <p class="success"><?= $success ?></p>
            <?php elseif ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="text" name="phone" placeholder="Телефон" value="<?= $phone ?? '' ?>" required>
                <input type="date" name="app_date" value="<?= $app_date ?? '' ?>" required>

                <select name="service" required>
                    <option value="">Выберите вид транспорта</option>
                    <option value="Катер" <?= (($service ?? '') == 'Катер') ? 'selected' : '' ?>>Катер</option>
                    <option value="Круизный лайнер" <?= (($service ?? '') == 'Круизный лайнер') ? 'selected' : '' ?>>Круизный лайнер</option>
                    <option value="Яхта" <?= (($service ?? '') == 'Яхта') ? 'selected' : '' ?>>Яхта</option>
                </select>

                <div class="radio-group">
                    <label><input type="radio" name="payment" value="Наличные" <?= (($payment ?? '') == 'Наличные') ? 'checked' : '' ?> required> Наличные</label>
                    <label><input type="radio" name="payment" value="Перевод по телефону" <?= (($payment ?? '') == 'Перевод по телефону') ? 'checked' : '' ?>> Перевод по телефону</label>
                </div>
                <button type="submit">Отправить</button>
                <a href="mb.php">Назад</a>
            </form>
        </div>
    </main>
    <footer>Водить.РФ</footer>
</body>
</html>