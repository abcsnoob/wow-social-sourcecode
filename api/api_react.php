<?php
// api/api_react.php
// File này chỉ xử lý logic Reactions và trả về JSON

header('Content-Type: application/json');

// Đảm bảo $current_user_id và $db đã được load từ index.php
if ($current_user_id === 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập.']);
    exit;
}

$post_id = (int)($_GET['post_id'] ?? 0);
$reaction_type = $_GET['type'] ?? '';

if ($post_id === 0 || empty($reaction_type)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$allowed_reactions = ['like', 'love', 'haha', 'wow', 'sad'];
if (!in_array($reaction_type, $allowed_reactions)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Loại reaction không hợp lệ.']);
    exit;
}

$action = handleReaction($db, $post_id, $current_user_id, $reaction_type);

echo json_encode(['status' => 'success', 'action' => $action, 'reaction' => $reaction_type]);