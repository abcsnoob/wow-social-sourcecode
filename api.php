<?php
// api.php?who=username (OpenCloud API Endpoint)
session_start();
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json');

$username = $_GET['who'] ?? null;

if (!$username) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameter: who=username.']);
    exit;
}

// Security: Sanitize input
$safe_username = $db->escapeString($username);

// Query user data (Chỉ lấy các trường công khai)
$user_data = $db->querySingle("SELECT id, username, coin, last_checkin_day FROM users WHERE username = '{$safe_username}'", true);

if ($user_data) {
    // Bổ sung các thông tin phụ trợ
    $user_data['avatar_url'] = CDN_BASE_URL . CDN_AVATAR_PATH . ($user_data['id'] % 2 + 1) . '.png';
    $user_data['success'] = true;

    echo json_encode($user_data);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found.']);
}