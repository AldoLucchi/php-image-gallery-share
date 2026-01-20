<?php
require_once __DIR__ . '/config/env.php';

header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$password = $_POST['password'] ?? '';
$recaptchaToken = $_POST['recaptcha_token'] ?? '';

$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || 
               strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

$captchaValid = false;

if ($isLocalhost && empty($recaptchaToken)) {
    $captchaValid = true;
} elseif (!empty($recaptchaToken) && !empty($_ENV['RECAPTCHA_SECRET_KEY'])) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $_ENV['RECAPTCHA_SECRET_KEY'],
        'response' => $recaptchaToken,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    
    $context = stream_context_create($options);
    
    try {
        $result = @file_get_contents($url, false, $context);
        if ($result !== FALSE) {
            $response = json_decode($result, true);
            $captchaValid = $response['success'] ?? false;
        }
    } catch (Exception $e) {
        $captchaValid = false;
    }
}

if (!$captchaValid && !$isLocalhost) {
    echo json_encode([
        'success' => false, 
        'error' => 'Security verification failed.'
    ]);
    exit;
}

$correctPassword = $_ENV['APP_PASSWORD'] ?? '10.09.2024';

if ($password === $correctPassword) {
    $_SESSION['gallery_authenticated'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Date incorrect'
    ]);
}
?>