<?php
session_start();
require 'config.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], (int)$_POST['app_id']]);
    header('Location: admin.php');
    exit;
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$pages = ceil($total / $limit);

$stmt = $pdo->prepare("
    SELECT a.*, u.fullname 
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.id DESC 
    LIMIT ? OFFSET ?
");

$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="logo"><a href="index.php">Водить.РФ</a></div>
    <nav>
        <a href="admin.php">Админка</a>
        <a href="mb.php">Мои заявки</a>
        <a href="create.php">Новая заявка</a>
        <a href="logout.php">Выйти</a>
    </nav>
</header>
<main>
    <div class="container">
        <h2>Все заявки</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Пользователь</th><th></th><th>Телефон</th><th>Дата</th><th></th><th>Курс</th><th>Оплата</th><th>Статус</th><th>Отзыв</th><th>Действие</th></tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= $app['id'] ?></td>
                    <td><?= $app['fullname'] ?></td>
                    <td><?= $app['address'] ?></td>
                    <td><?= $app['phone'] ?></td>
                    <td><?= $app['app_date'] ?></td>
                    <td><?= $app['app_time'] ?></td>
                    <td><?= $app['service'] ?></td>
                    <td><?= $app['payment'] ?></td>
                    <td><?= $app['status'] ?></td>
                    <td><?= $app['review'] ?? '' ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Изменить статус заявки?');">
                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                            <select name="status">
                                <option value="Новая" <?= $app['status'] === 'Новая' ? 'selected' : '' ?>>Новая</option>
                                <option value="Идет обучение" <?= $app['status'] === 'Идет обучение' ? 'selected' : '' ?>>Идет обучение</option>
                                <option value="Завершено" <?= $app['status'] === 'Завершено' ? 'selected' : '' ?>>Завершено</option>
                            </select>
                            <button type="submit">OK</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
<footer>Водить.РФ</footer>
</body>
</html>