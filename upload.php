<?php
session_start();

if (!isset($_SESSION['gallery_authenticated']) || $_SESSION['gallery_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$uploadDir = __DIR__ . '/images/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$maxFileSize = 10 * 1024 * 1024;
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST only']);
    exit;
}

if (!isset($_FILES['photos'])) {
    echo json_encode(['error' => 'No files']);
    exit;
}

$uploadedFiles = [];

foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
    if (empty($tmpName)) continue;
    
    $fileName = $_FILES['photos']['name'][$key];
    $fileSize = $_FILES['photos']['size'][$key];
    $error = $_FILES['photos']['error'][$key];
    
    if ($error !== UPLOAD_ERR_OK) continue;
    if ($fileSize > $maxFileSize) continue;
    
    $fileType = mime_content_type($tmpName);
    if (!in_array($fileType, $allowedTypes)) continue;
    
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newFileName = time() . '_' . uniqid() . '.' . $extension;
    $destination = $uploadDir . $newFileName;
    
    if (move_uploaded_file($tmpName, $destination)) {
        $uploadedFiles[] = [
            'url' => '/images/' . $newFileName,
            'name' => $newFileName,
            'original' => $fileName
        ];
    }
}

if (empty($uploadedFiles)) {
    echo json_encode(['error' => 'No files uploaded']);
} else {
    echo json_encode([
        'success' => true,
        'count' => count($uploadedFiles),
        'files' => $uploadedFiles
    ]);
}
?>