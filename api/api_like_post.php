<?php
// api/api_like_post.php
// Chỉ chạy khi được gọi từ index.php (đã có session, config, db, functions)

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Giả định $current_user_id đã được xác định ở index.php trước khi require
$post_id = $_POST['post_id'] ?? null; 

if (!$post_id || !isset($current_user_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing post or user ID.']);
    exit;
}

$action_performed = likePost($db, $post_id, $current_user_id); 

echo json_encode([
    'success' => true,
    'post_id' => $post_id,
    'action' => $action_performed // 'liked' hoặc 'unliked'
]);
