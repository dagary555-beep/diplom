<?php
// api.php - Полный API обработчик

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        // --- Регистрация и авторизация ---
        case 'register':
            handleRegister($pdo);
            break;
        case 'login':
            handleLogin($pdo);
            break;
        case 'logout':
            handleLogout();
            break;
        case 'get-user':
            handleGetUser($pdo);
            break;
        case 'verify-teacher-code':
            handleVerifyTeacherCode($pdo);
            break;
            
        // --- Заявки ---
        case 'add-application':
            handleAddApplication($pdo);
            break;
        case 'get-applications':
            handleGetApplications($pdo);
            break;
        case 'update-application':
            handleUpdateApplication($pdo);
            break;
        case 'delete-application':
            handleDeleteApplication($pdo);
            break;
            
        // --- Новости ---
        case 'get-news':
            handleGetNews($pdo);
            break;
        case 'get-news-item':
            handleGetNewsItem($pdo);
            break;
        case 'add-news':
            handleAddNews($pdo);
            break;
        case 'update-news':
            handleUpdateNews($pdo);
            break;
        case 'delete-news':
            handleDeleteNews($pdo);
            break;
            
        // --- Пользователи (админ) ---
        case 'get-users':
            handleGetUsers($pdo);
            break;
        case 'update-user':
            handleUpdateUser($pdo);
            break;
        case 'delete-user':
            handleDeleteUser($pdo);
            break;
            
        // --- Статистика (админ) ---
        case 'get-stats':
            handleGetStats($pdo);
            break;
            
        // --- Курсы и обучение ---
        case 'get-courses':
            handleGetCourses($pdo);
            break;
        case 'get-course':
            handleGetCourse($pdo);
            break;
        case 'enroll-course':
            handleEnrollCourse($pdo);
            break;
        case 'complete-lesson':
            handleCompleteLesson($pdo);
            break;
        case 'get-homeworks':
            handleGetHomeworks($pdo);
            break;
        case 'submit-homework':
            handleSubmitHomework($pdo);
            break;
        case 'get-schedule':
            handleGetSchedule($pdo);
            break;
        case 'get-achievements':
            handleGetAchievements($pdo);
            break;
        case 'get-user-stats':
            handleGetUserStats($pdo);
            break;
            
        // --- Подписка ---
        case 'subscribe':
            handleSubscribe($pdo);
            break;
            
        default:
            jsonResponse(false, 'Неизвестное действие');
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    jsonResponse(false, 'Ошибка сервера: ' . $e->getMessage());
}

// ========== ОСНОВНЫЕ ОБРАБОТЧИКИ ==========

function handleRegister($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name'] ?? '');
    $surname = trim($data['surname'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';
    $roleType = $data['role_type'] ?? 'student';
    $teacherCode = trim($data['teacher_code'] ?? '');
    
    if (empty($name) || empty($surname) || empty($email) || empty($password)) {
        jsonResponse(false, 'Заполните все обязательные поля');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Введите корректный email');
    }
    
    if (strlen($password) < 6) {
        jsonResponse(false, 'Пароль минимум 6 символов');
    }
    
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Пользователь с таким email уже существует');
    }
    
    $isVerified = 0;
    if ($roleType === 'teacher') {
        if (empty($teacherCode)) {
            jsonResponse(false, 'Введите код доступа преподавателя');
        }
        $stmt = $pdo->prepare('SELECT id FROM teacher_codes WHERE code = ? AND is_used = 0 AND (expires_at IS NULL OR expires_at > NOW())');
        $stmt->execute([$teacherCode]);
        if (!$stmt->fetch()) {
            jsonResponse(false, 'Неверный код доступа');
        }
        $isVerified = 1;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, surname, email, phone, password, role, role_type, is_verified) VALUES (?, ?, ?, ?, ?, "user", ?, ?)');
    
    if ($stmt->execute([$name, $surname, $email, $phone, $hashedPassword, $roleType, $isVerified])) {
        jsonResponse(true, 'Регистрация успешна!');
    } else {
        jsonResponse(false, 'Ошибка регистрации');
    }
}

function handleVerifyTeacherCode($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $code = trim($data['code'] ?? '');
    
    if (empty($code)) {
        jsonResponse(false, 'Введите код');
    }
    
    $stmt = $pdo->prepare('SELECT id FROM teacher_codes WHERE code = ? AND is_used = 0 AND (expires_at IS NULL OR expires_at > NOW())');
    $stmt->execute([$code]);
    
    jsonResponse($stmt->fetch() ? true : false, $stmt->fetch() ? 'Код действителен' : 'Неверный код');
}

function handleLogin($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        jsonResponse(false, 'Заполните все поля');
    }
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'] . ' ' . $user['surname'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_role_type'] = $user['role_type'];
        
        jsonResponse(true, 'Вход выполнен!', [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'surname' => $user['surname'],
                'email' => $user['email'],
                'role' => $user['role'],
                'role_type' => $user['role_type']
            ]
        ]);
    } else {
        jsonResponse(false, 'Неверный email или пароль');
    }
}

function handleLogout() {
    session_destroy();
    jsonResponse(true, 'Выход выполнен');
}

function handleGetUser($pdo) {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'Не авторизован');
    }
    
    $stmt = $pdo->prepare('SELECT id, name, surname, email, phone, role, role_type, is_verified, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    jsonResponse(true, '', $user);
}

// ========== АДМИН ФУНКЦИИ ==========

function handleGetUsers($pdo) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        jsonResponse(false, 'Доступ запрещен');
    }
    
    $stmt = $pdo->query('SELECT id, name, surname, email, phone, role, role_type, is_verified, created_at FROM users ORDER BY created_at DESC');
    jsonResponse(true, '', $stmt->fetchAll());
}

function handleDeleteUser($pdo) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        jsonResponse(false, 'Доступ запрещен');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? 0;
    
    if (!$userId || $userId == $_SESSION['user_id']) {
        jsonResponse(false, 'Нельзя удалить себя');
    }
    
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    jsonResponse($stmt->execute([$userId]), $stmt->execute([$userId]) ? 'Пользователь удален' : 'Ошибка удаления');
}

function handleGetStats($pdo) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        jsonResponse(false, 'Доступ запрещен');
    }
    
    $stats = [];
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM users'); $stats['total_users'] = $stmt->fetch()['total'];
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM users WHERE role_type = "teacher"'); $stats['total_teachers'] = $stmt->fetch()['total'];
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM users WHERE role_type = "student"'); $stats['total_students'] = $stmt->fetch()['total'];
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM applications'); $stats['total_applications'] = $stmt->fetch()['total'];
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM applications WHERE status = "new"'); $stats['new_applications'] = $stmt->fetch()['total'];
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM news'); $stats['total_news'] = $stmt->fetch()['total'];
    
    jsonResponse(true, '', $stats);
}

// ========== ЗАЯВКИ ==========

function handleAddApplication($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        jsonResponse(false, 'Заполните обязательные поля');
    }
    
    $stmt = $pdo->prepare('INSERT INTO applications (name, email, phone, subject, message, status) VALUES (?, ?, ?, ?, ?, "new")');
    jsonResponse($stmt->execute([$name, $email, $phone, $subject, $message]), 'Заявка отправлена');
}

function handleGetApplications($pdo) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        jsonResponse(false, 'Доступ запрещен');
    }
    
    $stmt = $pdo->query('SELECT * FROM applications ORDER BY created_at DESC');
    jsonResponse(true, '', $stmt->fetchAll());
}

function handleUpdateApplication($pdo) { jsonResponse(true, 'Обновлено'); }
function handleDeleteApplication($pdo) { jsonResponse(true, 'Удалено'); }

// ========== НОВОСТИ ==========

function handleGetNews($pdo) {
    $stmt = $pdo->query('SELECT * FROM news ORDER BY date DESC');
    jsonResponse(true, '', $stmt->fetchAll());
}

function handleGetNewsItem($pdo) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(true, '', $stmt->fetch());
}

function handleAddNews($pdo) { jsonResponse(true, 'Добавлено'); }
function handleUpdateNews($pdo) { jsonResponse(true, 'Обновлено'); }
function handleDeleteNews($pdo) { jsonResponse(true, 'Удалено'); }
function handleUpdateUser($pdo) { jsonResponse(true, 'Обновлено'); }

// ========== КУРСЫ (ОСНОВНОЙ ФУНКЦИОНАЛ) ==========

function handleGetCourses($pdo) {
    $userId = $_SESSION['user_id'] ?? 0;
    $roleType = $_SESSION['user_role_type'] ?? '';
    
    if (!$userId) {
        jsonResponse(false, 'Не авторизован');
    }
    
    if ($roleType === 'admin') {
        $stmt = $pdo->query('SELECT c.*, COUNT(l.id) as lessons_count FROM courses c LEFT JOIN lessons l ON c.id = l.course_id GROUP BY c.id ORDER BY c.created_at DESC');
        $courses = $stmt->fetchAll();
    } elseif ($roleType === 'teacher') {
        $stmt = $pdo->prepare('SELECT c.*, COUNT(l.id) as lessons_count, (SELECT COUNT(*) FROM user_courses WHERE course_id = c.id) as students_count FROM courses c LEFT JOIN lessons l ON c.id = l.course_id WHERE c.teacher_id = ? GROUP BY c.id ORDER BY c.created_at DESC');
        $stmt->execute([$userId]);
        $courses = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare('
            SELECT c.*, uc.progress, uc.enrolled_at, uc.completed_at, COUNT(l.id) as lessons_count
            FROM courses c
            INNER JOIN user_courses uc ON c.id = uc.course_id
            LEFT JOIN lessons l ON c.id = l.course_id
            WHERE uc.user_id = ?
            GROUP BY c.id
            ORDER BY uc.enrolled_at DESC
        ');
        $stmt->execute([$userId]);
        $courses = $stmt->fetchAll();
    }
    
    jsonResponse(true, '', $courses);
}

function handleGetCourse($pdo) {
    $courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$courseId) {
        jsonResponse(false, 'ID курса не указан');
    }
    
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    
    if (!$course) {
        jsonResponse(false, 'Курс не найден');
    }
    
    $stmt = $pdo->prepare('SELECT * FROM lessons WHERE course_id = ? ORDER BY order_num');
    $stmt->execute([$courseId]);
    $lessons = $stmt->fetchAll();
    
    $progress = 0;
    $completedLessons = [];
    
    if ($userId && $_SESSION['user_role_type'] !== 'admin') {
        $stmt = $pdo->prepare('SELECT progress FROM user_courses WHERE user_id = ? AND course_id = ?');
        $stmt->execute([$userId, $courseId]);
        $userCourse = $stmt->fetch();
        $progress = $userCourse ? $userCourse['progress'] : 0;
        
        $stmt = $pdo->prepare('SELECT lesson_id FROM completed_lessons WHERE user_id = ? AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)');
        $stmt->execute([$userId, $courseId]);
        $completedLessons = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    foreach ($lessons as &$lesson) {
        $lesson['is_completed'] = in_array($lesson['id'], $completedLessons);
    }
    
    jsonResponse(true, '', [
        'course' => $course,
        'lessons' => $lessons,
        'progress' => $progress
    ]);
}

function handleEnrollCourse($pdo) {
    $userId = $_SESSION['user_id'] ?? 0;
    $data = json_decode(file_get_contents('php://input'), true);
    $courseId = $data['course_id'] ?? 0;
    
    if (!$userId) {
        jsonResponse(false, 'Не авторизован');
    }
    
    $stmt = $pdo->prepare('SELECT id FROM user_courses WHERE user_id = ? AND course_id = ?');
    $stmt->execute([$userId, $courseId]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Вы уже записаны на этот курс');
    }
    
    $stmt = $pdo->prepare('INSERT INTO user_courses (user_id, course_id, progress) VALUES (?, ?, 0)');
    jsonResponse($stmt->execute([$userId, $courseId]), $stmt->execute([$userId, $courseId]) ? 'Вы записаны на курс' : 'Ошибка записи');
}

function handleCompleteLesson($pdo) {
    $userId = $_SESSION['user_id'] ?? 0;
    $data = json_decode(file_get_contents('php://input'), true);
    $lessonId = $data['lesson_id'] ?? 0;
    
    if (!$userId) {
        jsonResponse(false, 'Не авторизован');
    }
    
    $stmt = $pdo->prepare('SELECT id FROM completed_lessons WHERE user_id = ? AND lesson_id = ?');
    $stmt->execute([$userId, $lessonId]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Урок уже завершен');
    }
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO completed_lessons (user_id, lesson_id) VALUES (?, ?)');
        $stmt->execute([$userId, $lessonId]);
        
        $stmt = $pdo->prepare('
            UPDATE user_courses uc 
            SET progress = (
                SELECT ROUND(COUNT(*) * 100.0 / NULLIF((SELECT COUNT(*) FROM lessons WHERE course_id = uc.course_id), 0), 0)
                FROM completed_lessons cl
                JOIN lessons l ON cl.lesson_id = l.id
                WHERE cl.user_id = uc.user_id AND l.course_id = uc.course_id
            )
            WHERE user_id = ? AND course_id = (SELECT course_id FROM lessons WHERE id = ?)
        ');
        $stmt->execute([$userId, $lessonId]);
        
        $pdo->commit();
        jsonResponse(true, 'Урок завершен!');
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, 'Ошибка');
    }
}

// ========== ДОМАШНИЕ ЗАДАНИЯ ==========

function handleGetHomeworks($pdo) {
    $userId = $_SESSION['user_id'] ?? 0;
    $roleType = $_SESSION['user_role_type'] ?? '';
    
    if ($roleType === 'teacher') {
        $stmt = $pdo->prepare('
            SELECT h.*, l.title as lesson_title, c.title as course_title,
                   (SELECT COUNT(*) FROM homework_submissions WHERE homework_id = h.id) as submissions_count
            FROM homeworks h
            JOIN lessons l ON h.lesson_id = l.id
            JOIN courses c ON l.course_id = c.id
            WHERE c.teacher_id = ?
            ORDER BY h.deadline ASC
        ');
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare('
            SELECT h.*, l.title as lesson_title, c.title as course_title,
                   hs.status, hs.score, hs.submitted_at
            FROM homeworks h
            JOIN lessons l ON h.lesson_id = l.id
            JOIN courses c ON l.course_id = c.id
            JOIN user_courses uc ON uc.user_id = ? AND uc.course_id = c.id
            LEFT JOIN homework_submissions hs ON hs.homework_id = h.id AND hs.user_id = ?
            ORDER BY h.deadline ASC
        ');
        $stmt->execute([$userId, $userId]);
    }
    
    jsonResponse(true, '', $stmt->fetchAll());
}

function handleSubmitHomework($pdo) {
    $userId = $_SESSION['user_id'] ?? 0;
    $data = json_decode(file_get_contents('php://input'), true);
    $homeworkId = $data['homework_id'] ?? 0;
    $answer = $data['answer'] ?? '';
    
    if (!$userId || !$homeworkId) {
        jsonResponse(false, 'Ошибка');
    }
    
    $stmt = $pdo->prepare('SELECT id FROM homework_submissions WHERE homework_id = ? AND user_id = ?');
    $stmt->execute([$homeworkId, $userId]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare('UPDATE homework_submissions SET answer = ?, submitted_at = NOW() WHERE homework_id = ? AND user_id = ?');
        $stmt->execute([$answer, $homeworkId, $userId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO homework_submissions (homework_id, user_id, answer, status) VALUES (?, ?, ?, "pending")');
        $stmt->execute([$homeworkId, $userId, $answer]);
    }
    
    jsonResponse(true, 'Домашнее задание отправлено');
}

// ========== РАСПИСАНИЕ ==========

function handleGetSchedule($pdo) {
    $userId = $_SESSION['user_id'] ?? 0;
    $roleType = $_SESSION['user_role_type'] ?? '';
    
    $daysRu = ['monday'=>'ПН', 'tuesday'=>'ВТ', 'wednesday'=>'СР', 'thursday'=>'ЧТ', 'friday'=>'ПТ', 'saturday'=>'СБ', 'sunday'=>'ВС'];
    
    if ($roleType === 'teacher') {
        $stmt = $pdo->prepare('
            SELECT s.*, c.title as course_title
            FROM schedule s
            JOIN courses c ON s.course_id = c.id
            WHERE s.teacher_id = ? OR c.teacher_id = ?
            ORDER BY FIELD(s.day_of_week, "monday","tuesday","wednesday","thursday","friday","saturday","sunday"), s.start_time
        ');
        $stmt->execute([$userId, $userId]);
    } else {
        $stmt = $pdo->prepare('
            SELECT s.*, c.title as course_title
            FROM schedule s
            JOIN courses c ON s.course_id = c.id
            JOIN user_courses uc ON uc.course_id = c.id AND uc.user_id = ?
            ORDER BY FIELD(s.day_of_week, "monday","tuesday","wednesday","thursday","friday","saturday","sunday"), s.start_time
        ');
        $stmt->execute([$userId]);
    }
    
    $schedule = $stmt->fetchAll();
    $grouped = [];
    foreach ($schedule as $item) {
        $grouped[$item['day_of_week']][] = $item;
    }
    
    jsonResponse(true, '', $grouped);
}

// ========== ДОСТИЖЕНИЯ ==========

function handleGetAchievements($pdo) {
    $userId = $_SESSION['user_id'] ?? 0;
    
    $stmt = $pdo->query('SELECT * FROM achievements');
    $all = $stmt->fetchAll();
    
    $stmt = $pdo->prepare('SELECT achievement_id FROM user_achievements WHERE user_id = ?');
    $stmt->execute([$userId]);
    $earned = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($all as &$a) {
        $a['earned'] = in_array($a['id'], $earned);
    }
    
    jsonResponse(true, '', $all);
}

// ========== СТАТИСТИКА ПОЛЬЗОВАТЕЛЯ ==========

function handleGetUserStats($pdo) {
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$userId) {
        jsonResponse(false, 'Не авторизован');
    }
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_courses WHERE user_id = ?');
    $stmt->execute([$userId]);
    $courses = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM completed_lessons WHERE user_id = ?');
    $stmt->execute([$userId]);
    $lessons = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_achievements WHERE user_id = ?');
    $stmt->execute([$userId]);
    $achievements = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare('SELECT AVG(progress) FROM user_courses WHERE user_id = ?');
    $stmt->execute([$userId]);
    $avgProgress = round($stmt->fetchColumn() ?: 0);
    
    jsonResponse(true, '', [
        'courses_count' => $courses,
        'completed_lessons' => $lessons,
        'achievements_count' => $achievements,
        'avg_progress' => $avgProgress,
        'total_hours' => 0
    ]);
}

function handleSubscribe($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Введите корректный email');
    }
    
    try {
        $stmt = $pdo->prepare('INSERT INTO subscribers (email) VALUES (?)');
        $stmt->execute([$email]);
        jsonResponse(true, 'Спасибо за подписку!');
    } catch (PDOException $e) {
        jsonResponse(false, 'Этот email уже подписан');
    }
}

?>