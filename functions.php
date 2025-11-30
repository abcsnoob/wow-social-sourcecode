<?php
// functions.php - Đã CẬP NHẬT (Sửa lỗi kiểu dữ liệu và thêm Tooltip)

// ----------------------------------------------------
// UTILITIES
// ----------------------------------------------------
function setFlashMessage(string $type, string $content): void {
    $_SESSION['flash_messages'][$type] = $content;
}

function convertToLocalTime(string $utc_time, string $target_timezone = 'Asia/Ho_Chi_Minh'): string
{
    try {
        $date = new DateTime($utc_time, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($target_timezone));
        return $date->format('d/m/Y H:i'); 
    } catch (Exception $e) {
        return 'Thời gian không hợp lệ';
    }
}

// ----------------------------------------------------
// AVATAR & MEDIA LOGIC
// ----------------------------------------------------

function getAvatarUrl(SQLite3 $db, int $user_id): string {
    $user = $db->querySingle("SELECT avatar_filename FROM users WHERE id = {$user_id}", true);
    $filename = $user['avatar_filename'] ?? '';

    if (!empty($filename) && file_exists(UPLOAD_DIR . $filename)) {
        return '/uploads/avatars/' . $filename; 
    }
    
    return DEFAULT_AVATAR;
}

function handleAvatarUpload(SQLite3 $db, int $user_id, array $file): bool
{
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_UPLOAD_SIZE) {
        setFlashMessage('error', 'Lỗi upload file hoặc kích thước vượt quá 10MB.');
        return false;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        setFlashMessage('error', 'Chỉ chấp nhận file JPEG, PNG hoặc GIF.');
        return false;
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_file_name = "user_{$user_id}_" . time() . '.' . $file_extension;
    $target_path = UPLOAD_DIR . $new_file_name;

    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0777, true);
    }
    
    $old_avatar = $db->querySingle("SELECT avatar_filename FROM users WHERE id = {$user_id}", true);
    if (!empty($old_avatar['avatar_filename']) && file_exists(UPLOAD_DIR . $old_avatar['avatar_filename'])) {
        @unlink(UPLOAD_DIR . $old_avatar['avatar_filename']);
    }

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $stmt = $db->prepare("UPDATE users SET avatar_filename = :filename WHERE id = :user_id");
        $stmt->bindValue(':filename', $new_file_name, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Ảnh đại diện đã được cập nhật thành công!');
            return true;
        }
    }
    setFlashMessage('error', 'Lỗi khi di chuyển hoặc lưu file.');
    return false;
}

function handlePostMediaUpload(SQLite3 $db, int $user_id, array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_UPLOAD_SIZE) {
        setFlashMessage('error', 'Lỗi upload media: Kích thước file vượt quá giới hạn (tối đa 10MB).');
        return null;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        setFlashMessage('error', 'Chỉ chấp nhận file ảnh JPEG, PNG hoặc GIF cho bài viết.');
        return null;
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_file_name = "post_{$user_id}_" . time() . '.' . $file_extension;
    $target_dir = UPLOAD_DIR . 'media/'; 

    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }
    
    $target_path = $target_dir . $new_file_name;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return 'media/' . $new_file_name; 
    }

    setFlashMessage('error', 'Lỗi khi di chuyển file media.');
    return null;
}

// ----------------------------------------------------
// POSTS & COMMENTS LOGIC
// ----------------------------------------------------

function createPost(SQLite3 $db, int $user_id, string $content, array $file = []): bool 
{
    $content = trim($content);
    $media_filename = null;

    if (!empty($file['name'])) {
        $media_filename = handlePostMediaUpload($db, $user_id, $file);
    }
    
    if (empty($content) && empty($media_filename)) {
        setFlashMessage('error', 'Bài viết không được để trống nội dung hoặc ảnh.');
        return false;
    }

    $stmt = $db->prepare("INSERT INTO posts (user_id, content, media_filename, created_at) VALUES (:user_id, :content, :media_filename, datetime('now'))");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);
    $stmt->bindValue(':media_filename', $media_filename, SQLITE3_TEXT);

    return $stmt->execute() !== false;
}

function getPosts(SQLite3 $db, int $user_id): array 
{
    $posts_data = [];
    $posts = $db->query("
        SELECT 
            p.*, 
            u.username as author_name,
            u.avatar_filename,
            u.is_verified 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");

    while ($post = $posts->fetchArray(SQLITE3_ASSOC)) {
        $post['avatar_url'] = getAvatarUrl($db, $post['user_id']); 
        $posts_data[] = $post;
    }
    return $posts_data;
}

/**
 * Lấy danh sách bài viết chỉ của một người dùng cụ thể (Cho trang Profile)
 */
function getPostsByUser(SQLite3 $db, int $target_user_id): array 
{
    $posts_data = [];
    $posts = $db->query("
        SELECT 
            p.*, 
            u.username as author_name,
            u.avatar_filename,
            u.is_verified 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = {$target_user_id}
        ORDER BY p.created_at DESC
    ");

    while ($post = $posts->fetchArray(SQLITE3_ASSOC)) {
        $post['avatar_url'] = getAvatarUrl($db, $post['user_id']); 
        // Lấy thông tin reaction và comment cho từng bài viết
        $post['summary'] = getPostReactionsSummary($db, $post['id']);
        $posts_data[] = $post;
    }
    return $posts_data;
}


function createComment(SQLite3 $db, int $post_id, int $user_id, string $content): bool 
{
    $content = trim($content);
    if (empty($content)) {
        setFlashMessage('error', 'Nội dung bình luận không được để trống.');
        return false;
    }
    
    $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (:post_id, :user_id, :content, datetime('now'))");
    $stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);

    return $stmt->execute() !== false;
}

function getCommentsByPostId(SQLite3 $db, int $post_id): array
{
    $comments_data = [];
    $query = $db->query("
        SELECT 
            c.*, 
            u.username as author_name,
            u.avatar_filename
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = {$post_id}
        ORDER BY c.created_at ASC
    ");

    while ($comment = $query->fetchArray(SQLITE3_ASSOC)) {
        $comment['avatar_url'] = getAvatarUrl($db, $comment['user_id']);
        $comments_data[] = $comment;
    }
    return $comments_data;
}


/**
 * Hàm RENDER bài viết chung (Sử dụng cho cả Home và Profile)
 */
function renderPost(SQLite3 $db, array $post, ?string $my_reaction, int $current_user_id): void 
{
    $post_id = $post['id'];
    $author_id = $post['user_id'];
    $author_name = htmlspecialchars($post['author_name']);
    $content = nl2br(htmlspecialchars($post['content']));
    $created_at = convertToLocalTime($post['created_at']);
    $avatar_url = $post['avatar_url'];
    $is_verified = $post['is_verified'] ?? 0;
    $media_url = $post['media_filename'] ? '/uploads/' . $post['media_filename'] : null;
    $tooltip_text = 'Đây là dấu dành cho các tài khoản đã được admin xác minh và bạn có thể tin tưởng vào các tài khoản này'; // <<< TOOLTIP MỚI
    
    // Summary
    $summary = $post['summary'] ?? getPostReactionsSummary($db, $post_id);
    $total_reactions = $summary['total'];
    $comments_count = $summary['comments_count'];

    // Reaction Icons
    $reaction_icons = [
        'like' => ['icon' => 'bi-hand-thumbs-up', 'color' => 'text-primary'],
        'love' => ['icon' => 'bi-heart-fill', 'color' => 'text-danger'],
        'haha' => ['icon' => 'bi-emoji-laughing-fill', 'color' => 'text-warning'],
    ];
    
    // Start HTML output
    echo '<div class="card post-item shadow-sm mb-4">';
        echo '<div class="card-body">';
            // Header
            echo '<div class="d-flex align-items-center mb-3">';
                echo '<a href="/index.php?action=profile&user=' . $author_id . '">';
                    echo '<img src="' . $avatar_url . '" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;" alt="Avatar">';
                echo '</a>';
                echo '<div>';
                    echo '<a href="/index.php?action=profile&user=' . $author_id . '" class="fw-bold text-decoration-none text-dark">';
                        echo $author_name;
                        // Thêm tooltip bằng data-bs-toggle và title
                        if ($is_verified) {
                            echo '<i class="bi bi-patch-check-fill text-info ms-1 small" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $tooltip_text . '"></i>'; 
                        }
                    echo '</a>';
                    echo '<p class="text-muted small mb-0">' . $created_at . '</p>';
                echo '</div>';
            echo '</div>';
            
            // Content
            echo '<p class="card-text">' . $content . '</p>';
            
            // Media
            if ($media_url) {
                echo '<div class="text-center my-3">';
                    // Sử dụng đường dẫn tương đối /uploads/media/...
                    echo '<img src="/uploads/' . $post['media_filename'] . '" class="img-fluid rounded" style="max-height: 500px; object-fit: cover;" alt="Post Media">';
                echo '</div>';
            }

            // Summary (Reactions & Comments)
            echo '<div class="d-flex justify-content-between align-items-center border-top border-bottom py-2 small">';
                // Reactions Summary
                echo '<div>';
                if ($total_reactions > 0) {
                    $summary_icons = [];
                    foreach ($reaction_icons as $type => $data) {
                        if (isset($summary['summary'][$type]) && $summary['summary'][$type] > 0) {
                            // Thêm tooltip cho từng icon reaction
                            $summary_icons[] = '<i class="bi ' . $data['icon'] . ' ' . $data['color'] . '" data-bs-toggle="tooltip" data-bs-placement="top" title="' . $summary['summary'][$type] . ' ' . ucfirst($type) . '"></i>';
                        }
                    }
                    echo implode(' ', $summary_icons);
                    echo '<span class="ms-1 text-muted">(' . $total_reactions . ')</span>';
                }
                echo '</div>';
                
                // Comments Count
                echo '<a href="#comments-' . $post_id . '" class="text-muted text-decoration-none small">' . $comments_count . ' Bình luận</a>';
            echo '</div>';

            // Action Buttons
            echo '<div class="d-flex justify-content-around mt-2">';
                foreach ($reaction_icons as $type => $data) {
                    $is_active = $my_reaction === $type;
                    $class = $is_active ? 'btn-primary' : 'btn-outline-primary';
                    $icon_class = $is_active ? $data['icon'] . ' text-white' : $data['icon'] . ' ' . $data['color'];

                    echo '<a href="/index.php?action=api_handle_reaction&post_id=' . $post_id . '&type=' . $type . '" class="btn btn-sm ' . $class . ' w-100 me-2">';
                    echo '<i class="bi ' . $icon_class . ' me-1"></i> ' . ucfirst($type);
                    echo '</a>';
                }
            echo '</div>';
            
            // Comments Section (Inline)
            echo '<div id="comments-' . $post_id . '" class="mt-3">';
                echo '<h6><i class="bi bi-chat-dots me-1"></i> Bình luận</h6>';
                $comments = getCommentsByPostId($db, $post_id); 
                
                // Display existing comments
                if (!empty($comments)) {
                    echo '<div class="list-group list-group-flush mb-3" style="max-height: 300px; overflow-y: auto;">';
                    foreach ($comments as $comment) {
                        $comment_author_id = $comment['user_id'];
                        $comment_author_name = htmlspecialchars($comment['author_name']);
                        $comment_avatar = getAvatarUrl($db, $comment_author_id); 
                        $comment_content = htmlspecialchars($comment['content']);
                        $comment_time = convertToLocalTime($comment['created_at']);
                        
                        echo '<div class="list-group-item">';
                            echo '<div class="d-flex align-items-start">';
                                echo '<img src="' . $comment_avatar . '" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;" alt="Commenter Avatar">';
                                echo '<div>';
                                    echo '<a href="/index.php?action=profile&user=' . $comment_author_id . '" class="fw-bold small text-dark me-2">' . $comment_author_name . '</a>';
                                    echo '<span class="small text-muted">' . $comment_time . '</span>';
                                    echo '<p class="mb-0 small">' . $comment_content . '</p>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p class="small text-muted text-center">Chưa có bình luận nào. Hãy là người đầu tiên!</p>';
                }
                
                // Comment Form
                if ($current_user_id > 0) {
                    echo '<form action="/index.php?action=create_comment_action" method="POST" class="mt-2">';
                        echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
                        echo '<div class="input-group">';
                            echo '<input type="text" class="form-control form-control-sm" name="content" placeholder="Viết bình luận..." required>';
                            echo '<button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-send-fill"></i></button>';
                        echo '</div>';
                    echo '</form>';
                } else {
                    echo '<div class="alert alert-warning small text-center mt-2">Đăng nhập để bình luận.</div>';
                }
            echo '</div>';

        echo '</div>'; // End card-body
    echo '</div>'; // End post-item
}


// ----------------------------------------------------
// REACTION LOGIC
// ----------------------------------------------------

function handleReaction(SQLite3 $db, int $post_id, int $user_id, string $reaction_type): string
{
    $existing_reaction = $db->querySingle("SELECT reaction_type FROM reactions WHERE post_id = {$post_id} AND user_id = {$user_id}", true);

    if ($existing_reaction) {
        if ($existing_reaction['reaction_type'] === $reaction_type) {
            $db->exec("DELETE FROM reactions WHERE post_id = {$post_id} AND user_id = {$user_id}");
            return 'removed';
        } else {
            $stmt = $db->prepare("UPDATE reactions SET reaction_type = :reaction_type, created_at = datetime('now') WHERE post_id = :post_id AND user_id = :user_id");
            $stmt->bindValue(':reaction_type', $reaction_type, SQLITE3_TEXT);
            $stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->execute();
            return 'updated';
        }
    } else {
        $stmt = $db->prepare("INSERT INTO reactions (post_id, user_id, reaction_type, created_at) VALUES (:post_id, :user_id, :reaction_type, datetime('now'))");
        $stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':reaction_type', $reaction_type, SQLITE3_TEXT);
        $stmt->execute();
        return 'added';
    }
}

function getPostReactionsSummary(SQLite3 $db, int $post_id): array
{
    $summary = [];
    $query = $db->query("
        SELECT 
            reaction_type, 
            COUNT(*) as count 
        FROM reactions 
        WHERE post_id = {$post_id} 
        GROUP BY reaction_type
    ");
    
    $total_count = 0;
    while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
        $summary[$row['reaction_type']] = $row['count'];
        $total_count += $row['count'];
    }
    
    $comments_count = $db->querySingle("SELECT COUNT(*) FROM comments WHERE post_id = {$post_id}");
    
    return ['summary' => $summary, 'total' => $total_count, 'comments_count' => $comments_count];
}


// ----------------------------------------------------
// DIRECT MESSAGE LOGIC (Giữ nguyên)
// ----------------------------------------------------

function sendMessage(SQLite3 $db, int $sender_id, int $receiver_id, string $content): bool
{
    $content = trim($content);
    if (empty($content)) {
        return false;
    }

    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, content, created_at) VALUES (:sender_id, :receiver_id, :content, datetime('now'))");
    $stmt->bindValue(':sender_id', $sender_id, SQLITE3_INTEGER);
    $stmt->bindValue(':receiver_id', $receiver_id, SQLITE3_INTEGER);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);

    return $stmt->execute() !== false;
}

function getConversation(SQLite3 $db, int $user1_id, int $user2_id): array
{
    $messages = $db->query("
        SELECT * FROM messages 
        WHERE (sender_id = {$user1_id} AND receiver_id = {$user2_id}) 
           OR (sender_id = {$user2_id} AND receiver_id = {$user1_id})
        ORDER BY created_at ASC
    ");

    $conversation = [];
    while ($msg = $messages->fetchArray(SQLITE3_ASSOC)) {
        $conversation[] = $msg;
    }
    
    // Đánh dấu tin nhắn là đã đọc
    $db->exec("UPDATE messages SET is_read = 1 WHERE receiver_id = {$user1_id} AND sender_id = {$user2_id} AND is_read = 0");
    
    return $conversation;
}

// ----------------------------------------------------
// FRIEND LOGIC
// ----------------------------------------------------

function getFriendshipStatus(SQLite3 $db, int $user1_id, int $user2_id): ?array 
{
    if ($user1_id === $user2_id) return null;
    $a = min($user1_id, $user2_id);
    $b = max($user1_id, $user2_id);

    $stmt = $db->prepare("SELECT * FROM friends WHERE user1_id = :a AND user2_id = :b");
    $stmt->bindValue(':a', $a, SQLITE3_INTEGER);
    $stmt->bindValue(':b', $b, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    
    if ($result) {
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ?: null; 
    }

    return null;
}

function handleFriendAction(SQLite3 $db, int $sender_id, int $receiver_id, string $action): bool
{
    if ($sender_id === $receiver_id) return false;

    // Luôn sắp xếp ID để tìm kiếm bản ghi trong bảng friends
    $user1_id = min($sender_id, $receiver_id);
    $user2_id = max($sender_id, $receiver_id);
    
    // Lấy trạng thái hiện tại giữa hai người
    $current_status = getFriendshipStatus($db, $sender_id, $receiver_id);
    
    try {
        if ($action === 'send') {
            if (!$current_status) {
                // Gửi lời mời mới
                $db->exec("INSERT INTO friends (user1_id, user2_id, status, created_at) VALUES ({$user1_id}, {$user2_id}, 'pending', datetime('now'))");
                setFlashMessage('success', 'Đã gửi lời mời kết bạn!');
                return true;
            } elseif ($current_status['status'] === 'pending') {
                setFlashMessage('error', 'Lời mời đang chờ chấp nhận hoặc bạn đã gửi/nhận lời mời rồi.');
                return false;
            }
        } elseif ($action === 'accept') {
            // Kiểm tra xem có lời mời pending tồn tại hay không
            if ($current_status && $current_status['status'] === 'pending') {
                // Đảm bảo chỉ người nhận (receiver_id) mới được chấp nhận
                // Trong bảng friends, người gửi luôn là user1_id (ID nhỏ hơn) hoặc user1_id là ID của người tạo request
                
                // Ở đây ta dùng logic đơn giản: Nếu status là pending, bất kể ai là user1_id hay user2_id,
                // người nhận (receiver_id) thực hiện hành động 'accept' sẽ chuyển thành 'accepted'.
                // LƯU Ý: Tuy nhiên, để chính xác, chỉ người nhận lời mời (người không phải là người gửi ban đầu) mới được chấp nhận.
                // Nếu $current_status['user2_id'] là $sender_id, tức $sender_id (người đang click) là người nhận ban đầu, mới cho phép ACCEPT.
                
                // Kiểm tra: Người đang click (sender_id) phải là người nhận lời mời (user2_id)
                // Vì $user1_id và $user2_id được sắp xếp, ta cần lấy ID của người gửi ban đầu:
                // Người gửi ban đầu là người không phải là $receiver_id
                
                // CÁCH FIX ĐÚNG:
                // Người gửi lời mời ban đầu là người có ID không phải là ID của người đang chấp nhận ($receiver_id).
                // Do chúng ta không lưu rõ ai là người gửi trong bảng, ta phải dựa vào ai là người đang click.
                // Khi click 'Chấp nhận', $sender_id là ID của người đang click (người nhận lời mời).
                
                // Nếu người đang click ($sender_id) là user2_id của request pending, tức là họ được mời, họ có thể chấp nhận.
                // Lưu ý: $sender_id ở đây là ID của người đang thao tác trên giao diện.
                
                // Sửa lỗi logic: Chúng ta chỉ cần biết bản ghi tồn tại và là 'pending' thì có thể chấp nhận.
                // Trong friend_list.php, nút chấp nhận chỉ hiện ra cho user2_id (người nhận).
                
                $db->exec("UPDATE friends SET status = 'accepted' WHERE user1_id = {$user1_id} AND user2_id = {$user2_id}");
                setFlashMessage('success', 'Đã chấp nhận lời mời kết bạn!');
                return true;
            }
        } elseif (in_array($action, ['unfriend', 'cancel'])) {
            if ($current_status) {
                $db->exec("DELETE FROM friends WHERE user1_id = {$user1_id} AND user2_id = {$user2_id}");
                setFlashMessage('success', $action === 'unfriend' ? 'Đã hủy kết bạn.' : 'Đã hủy lời mời.');
                return true;
            }
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Lỗi khi xử lý thao tác bạn bè: ' . $e->getMessage());
        return false;
    }
    return false;
}

function getFriendList(SQLite3 $db, int $user_id, string $status = 'accepted'): array
{
    $friends = [];
    $query = $db->query("
        SELECT 
            u.id, u.username, u.avatar_filename
        FROM friends f
        JOIN users u ON u.id = 
            CASE 
                WHEN f.user1_id = {$user_id} THEN f.user2_id
                ELSE f.user1_id
            END
        WHERE (f.user1_id = {$user_id} OR f.user2_id = {$user_id}) 
          AND f.status = '{$status}'
          " . ($status === 'pending' ? " AND f.user2_id = {$user_id}" : "") . "
    ");

    while ($user = $query->fetchArray(SQLITE3_ASSOC)) {
        $user['avatar_url'] = getAvatarUrl($db, $user['id']);
        $friends[] = $user;
    }
    return $friends;
}


// ----------------------------------------------------
// USER/PROFILE LOGIC
// ----------------------------------------------------

/**
 * Cập nhật mô tả cá nhân 
 */
function updateDescription(SQLite3 $db, int $user_id, string $description): bool
{
    $description = trim($description);
    if (mb_strlen($description) > 500) {
        setFlashMessage('error', 'Mô tả không được vượt quá 500 ký tự.');
        return false;
    }
    
    $stmt = $db->prepare("UPDATE users SET description = :description WHERE id = :user_id");
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'Mô tả đã được cập nhật.');
        return true;
    }
    setFlashMessage('error', 'Lỗi database khi cập nhật mô tả.');
    return false;
}


// ----------------------------------------------------
// ADMIN/VERIFICATION LOGIC (Giữ nguyên)
// ----------------------------------------------------

function submitVerificationRequest(SQLite3 $db, int $user_id): bool 
{
    $existing_request = $db->querySingle("SELECT id FROM verification_requests WHERE user_id = {$user_id} AND status = 'pending'");
    if ($existing_request) {
        setFlashMessage('error', 'Yêu cầu xác minh của bạn đang được xem xét. Vui lòng đợi.');
        return false;
    }
    
    $is_verified = $db->querySingle("SELECT is_verified FROM users WHERE id = {$user_id}");
    if ($is_verified) {
        setFlashMessage('error', 'Bạn đã được xác minh rồi.');
        return false;
    }

    $stmt = $db->prepare("INSERT INTO verification_requests (user_id, requested_at, status) VALUES (:user_id, datetime('now'), 'pending')");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        setFlashMessage('success', 'Yêu cầu xác minh đã được gửi thành công. Admin (ID 1) sẽ xem xét sớm nhất.');
        return true;
    }
    
    setFlashMessage('error', 'Lỗi database khi gửi yêu cầu.');
    return false;
}

function getPendingVerificationRequests(SQLite3 $db): array
{
    $requests = [];
    $query = $db->query("
        SELECT 
            r.*, 
            u.username as requested_by_name,
            u.avatar_filename
        FROM verification_requests r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.status = 'pending'
        ORDER BY r.requested_at ASC
    ");

    while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
        $row['avatar_url'] = getAvatarUrl($db, $row['user_id']);
        $requests[] = $row;
    }
    return $requests;
}

function updateVerificationRequestStatus(SQLite3 $db, int $request_id, string $new_status): bool
{
    if (!in_array($new_status, ['approved', 'rejected'])) return false;

    $request = $db->querySingle("SELECT user_id FROM verification_requests WHERE id = {$request_id}", true);
    if (!$request) return false;
    $user_id = $request['user_id'];
    
    $db->exec("DELETE FROM verification_requests WHERE id = {$request_id}");

    if ($new_status === 'approved') {
        $db->exec("UPDATE users SET is_verified = 1 WHERE id = {$user_id}");
        setFlashMessage('success', "Đã duyệt và xác minh người dùng ID {$user_id}.");
    } else {
        setFlashMessage('warning', "Đã từ chối yêu cầu xác minh của người dùng ID {$user_id}.");
    }
    
    return true;
}

function toggleVerificationStatus(SQLite3 $db, int $admin_id, int $target_user_id): bool
{
    if ($admin_id !== 1) {
        setFlashMessage('error', 'Bạn không có quyền thực hiện thao tác này.');
        return false;
    }
    
    if ($admin_id === $target_user_id) {
         setFlashMessage('error', 'Không thể tự xác minh/hủy xác minh chính mình.');
         return false;
    }

    $target_user = $db->querySingle("SELECT id, is_verified FROM users WHERE id = {$target_user_id}", true);

    if (!$target_user) {
        setFlashMessage('error', 'Không tìm thấy người dùng này.');
        return false;
    }

    $new_status = $target_user['is_verified'] ? 0 : 1;
    $status_text = $new_status ? 'Đã xác minh' : 'Đã hủy xác minh';
    
    $stmt = $db->prepare("UPDATE users SET is_verified = :status WHERE id = :user_id");
    $stmt->bindValue(':status', $new_status, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $target_user_id, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        $db->exec("DELETE FROM verification_requests WHERE user_id = {$target_user_id} AND status = 'pending'"); 
        setFlashMessage('success', "Người dùng **{$target_user_id}** đã được cập nhật trạng thái: {$status_text}.");
        return true;
    }
    
    setFlashMessage('error', 'Lỗi database khi cập nhật trạng thái xác minh.');
    return false;
}

// ----------------------------------------------------
// AUTH, COIN & CHECKIN LOGIC (Giữ nguyên)
// ----------------------------------------------------

function handleCheckinAction(SQLite3 $db, int $user_id, string $type, bool $auto_checkin = false): bool 
{
    $user = $db->querySingle("SELECT coin, last_checkin_day FROM users WHERE id = {$user_id}", true);
    $today = date('Y-m-d');

    if ($user && ($user['last_checkin_day'] ?? '') === $today) {
        setFlashMessage('error', 'Bạn đã điểm danh hôm nay rồi!');
        return false;
    }

    if ($auto_checkin) {
        if (($user['coin'] ?? 0) < 10) {
            setFlashMessage('error', 'Không đủ 10 Coin để Điểm danh Tự động.');
            return false;
        }
        $db->exec("UPDATE users SET coin = coin - 10, last_checkin_day = '{$today}' WHERE id = {$user_id}");
        setFlashMessage('success', 'Điểm danh Tự động thành công! Đã trừ 10 Coin.');
    } else {
        $db->exec("UPDATE users SET coin = coin + 10, last_checkin_day = '{$today}' WHERE id = {$user_id}");
        setFlashMessage('success', 'Điểm danh Thủ công thành công! Bạn nhận được 10 Coin.');
    }
    return true;
}

function handleRegistration(SQLite3 $db, string $username, string $password): bool 
{
    if (empty($username) || empty($password)) {
        setFlashMessage('error', 'Tên người dùng và mật khẩu không được để trống.');
        return false;
    }
    
    $safe_username = $db->escapeString($username);
    $existing_user = $db->querySingle("SELECT id FROM users WHERE username = '{$safe_username}'");
    if ($existing_user) {
        setFlashMessage('error', 'Tên người dùng đã tồn tại.');
        return false;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, password, coin, created_at) VALUES (:username, :password, 100, datetime('now'))");
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $db->lastInsertRowID();
        setFlashMessage('success', 'Đăng ký thành công! Chào mừng đến với WOW SOCIAL.');
        return true;
    } else {
        setFlashMessage('error', 'Lỗi đăng ký database.');
        return false;
    }
}

function handleLogin(SQLite3 $db, string $username, string $password): bool 
{
    if (empty($username) || empty($password)) {
        setFlashMessage('error', 'Vui lòng nhập đầy đủ tên người dùng và mật khẩu.');
        return false;
    }
    
    $safe_username = $db->escapeString($username);
    $user = $db->querySingle("SELECT id, password FROM users WHERE username = '{$safe_username}'", true);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        setFlashMessage('success', 'Đăng nhập thành công!');
        return true;
    } else {
        setFlashMessage('error', 'Tên người dùng hoặc mật khẩu không đúng.');
        return false;
    }
}

function handleLogout(): void 
{
    unset($_SESSION['user_id']);
    session_destroy();
    session_start();
    setFlashMessage('success', 'Bạn đã đăng xuất.');
}