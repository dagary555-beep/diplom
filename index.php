<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Водить РФ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="logo"><a href="index.php">Водить.РФ</a></div>
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (($_SESSION['role'] ?? '') === 'admin') echo '<a href="admin.php">Админка</a>'; ?>
            <a href="mb.php">Мои заявки</a>
            <a href="create.php">Новая заявка</a>
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="login.php">Вход</a>
            <a href="register.php">Регистрация</a>
        <?php endif; ?>
    </nav>
</header>
<main>
    <div class="slider-box">
        <div class="slides">
            <div class="slide"><img src="img/2.jpg"></div>
            <div class="slide"><img src="img/6.jpg"></div>
            <div class="slide"><img src="img/11.jpg"></div>
            <div class="slide"><img src="img/12.jpg"></div>
        </div>
        <button class="btn prev" onclick="changeSlide(-1)">❮</button>
        <button class="btn next" onclick="changeSlide(1)">❯</button>
        <div class="dots"></div>
    </div>
    <div class="content">
        <h1>Курсы обучения вождению речного транспорта</h1>
    </div>
</main>
<footer>Водить.РФ</footer>
<script>
    let slideIndex = 0;
    const slides = document.querySelectorAll('.slide');
    const dotsDiv = document.querySelector('.dots');
    let timer;
    function update() {
        document.querySelector('.slides').style.transform = `translateX(-${slideIndex*100}%)`;
        document.querySelectorAll('.dot').forEach((dot,i)=>dot.classList.toggle('active',i===slideIndex));
    }
    function changeSlide(n) {
        slideIndex = (slideIndex + n + slides.length) % slides.length;
        update();
        resetTimer();
    }
    function resetTimer() {
        clearInterval(timer);
        timer = setInterval(()=> changeSlide(1), 3000);
    }
    slides.forEach((_,i)=>{
        let dot = document.createElement('span');
        dot.className = 'dot';
        dot.onclick = () => { slideIndex = i; update(); resetTimer(); };
        dotsDiv.appendChild(dot);
    });
    update();
    resetTimer();
</script>
</body>
</html>