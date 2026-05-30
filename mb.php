<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Мои заявки</title>
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
        <div class="container">
            <h2>Мои заявки</h2>
            <?php if (count($applications) === 0): ?>
                <p>Нет заявок. <a href="create.php">Создать</a></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Телефон</th>
                            <th>Дата</th>
                            <th>Курс</th>
                            <th>Оплата</th>
                            <th>Статус</th>
                            <th>Отзыв</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?= htmlspecialchars($app['phone']) ?></td>
                                <td><?= htmlspecialchars($app['app_date']) ?></td>
                                <td><?= htmlspecialchars($app['service']) ?></td>
                                <td><?= htmlspecialchars($app['payment']) ?></td>
                                <td><?= htmlspecialchars($app['status']) ?></td>
                                <td>
                                    <?php if ($app['status'] === 'Завершено'): ?>
                                        <form method="post" action="add_review.php">
                                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                            <input type="text" name="review" placeholder="Ваш отзыв" value="<?= htmlspecialchars($app['review'] ?? '') ?>">
                                            <button type="submit">Сохранить отзыв</button>
                                        </form>
                                    <?php else: ?>
                                        <?= htmlspecialchars($app['review'] ?? '—') ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    <footer>Водить.РФ</footer>
</body>
</html>