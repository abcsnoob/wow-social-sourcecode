<?php
// index.php (LOGIC VÀ CONTROLLER CHÍNH)
// Đã CẬP NHẬT: Thêm xử lý Mô tả và fix logic lời mời bạn bè Admin (ID 1)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// --- 1. REQUIRE VIEW/STYLE/FUNCTION/DB FILE ---
require_once 'style.php'; 
require_once 'db.php'; 
require_once 'functions.php';

// --- 2. KHAI BÁO CẤU HÌNH & BIẾN TOÀN CỤC ---
$current_user_id = $_SESSION['user_id'] ?? 0;
$current_user_info = null;

if ($current_user_id > 0) {
    $current_user_info = $db->querySingle("SELECT * FROM users WHERE id = {$current_user_id}", true);
    // Nếu người dùng tồn tại, lấy URL avatar
    if ($current_user_info) {
        $current_user_info['avatar_url'] = getAvatarUrl($db, $current_user_id);
    }
}

// Lấy action
$action = $_GET['action'] ?? 'home';

// =======================================================
// XỬ LÝ CÁC HÀNH ĐỘNG FORM (POST)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'register_action') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (handleRegistration($db, $username, $password)) {
            header('Location: /index.php'); exit;
        }
        header('Location: /index.php?action=register'); exit;
    } elseif ($action === 'login_action') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        if (handleLogin($db, $username, $password)) {
            header('Location: /index.php'); exit;
        }
        header('Location: /index.php?action=login'); exit;
    } elseif ($action === 'create_post_action') {
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập để đăng bài.');
            header('Location: /index.php?action=login'); exit;
        }
        $content = $_POST['content'] ?? '';
        $file = $_FILES['media_file'] ?? [];

        if (createPost($db, $current_user_id, $content, $file)) {
            setFlashMessage('success', 'Bài viết đã được đăng thành công!');
        }
        header('Location: /index.php'); exit;
    } elseif ($action === 'upload_avatar_action') {
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập.');
            header('Location: /index.php?action=login'); exit;
        }
        if (handleAvatarUpload($db, $current_user_id, $_FILES['avatar'])) {
             // success message đã được set trong hàm
        }
        header('Location: /index.php?action=avatar_setting'); exit;
    } elseif ($action === 'create_comment_action') {
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập để bình luận.');
            header('Location: /index.php?action=login'); exit;
        }
        $post_id = (int)($_POST['post_id'] ?? 0);
        $content = $_POST['content'] ?? '';

        if (createComment($db, $post_id, $current_user_id, $content)) {
            //setFlashMessage('success', 'Đã bình luận!');
        }
        header('Location: /index.php'); exit;
    } elseif ($action === 'send_message_action') {
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập để nhắn tin.');
            header('Location: /index.php?action=login'); exit;
        }
        $receiver_id = (int)($_POST['receiver_id'] ?? 0);
        $content = $_POST['content'] ?? '';

        sendMessage($db, $current_user_id, $receiver_id, $content);
        header('Location: /index.php?action=dm&user=' . $receiver_id);
        exit;
    } elseif ($action === 'update_description_action') { // <<< HÀNH ĐỘNG MỚI
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập.');
            header('Location: /index.php?action=login'); exit;
        }
        $description = $_POST['description'] ?? '';
        updateDescription($db, $current_user_id, $description);
        header('Location: /index.php?action=profile'); exit; 
    }
}

// =======================================================
// XỬ LÝ CÁC HÀNH ĐỘNG (GET)
// =======================================================
switch ($action) {
    case 'login':
        if ($current_user_id > 0) { header('Location: /index.php'); exit; }
        $page_title = 'Đăng nhập'; require_once 'views/login.php'; break;
    case 'register':
        if ($current_user_id > 0) { header('Location: /index.php'); exit; }
        $page_title = 'Đăng ký tài khoản'; require_once 'views/register.php'; break;
    case 'logout_action':
        handleLogout(); header('Location: /index.php'); exit;
        
    case 'home':
        if ($current_user_id === 0) {
            $page_title = 'Chào mừng'; require_once 'views/guest_home.php'; break;
        }
        $page_title = 'Bảng tin';
        $posts = getPosts($db, $current_user_id);
        require_once 'views/home_content.php'; break;
    
    case 'profile': // Đã CẬP NHẬT
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập để xem hồ sơ.');
            header('Location: /index.php?action=login'); exit;
        }
        $profile_id = $_GET['user'] ?? $current_user_id;
        $profile_data = $db->querySingle("SELECT * FROM users WHERE id = {$profile_id}", true);
        if (!$profile_data) { $page_title = 'Lỗi 404'; echo '<div class="alert alert-danger">Không tìm thấy hồ sơ người dùng này.</div>'; break; }
        
        $profile_data['avatar_url'] = getAvatarUrl($db, $profile_data['id']); 
        
        // LẤY BÀI VIẾT CỦA NGƯỜI DÙNG NÀY 
        $profile_posts = getPostsByUser($db, $profile_id); 
        
        $page_title = 'Hồ Sơ ' . htmlspecialchars($profile_data['username']);
        require_once 'views/profile_content.php';
        break;

    case 'avatar_setting':
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập.');
            header('Location: /index.php?action=login'); exit;
        }
        $page_title = 'Cài đặt Ảnh đại diện'; require_once 'views/avatar_setting.php'; break;
        
    case 'checkin_action':
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập.');
            header('Location: /index.php?action=login'); exit;
        }
        $type = $_GET['type'] ?? 'daily';
        $auto = isset($_GET['auto']);
        handleCheckinAction($db, $current_user_id, $type, $auto);
        header('Location: /index.php'); exit;
        
    case 'friend_list': // Đã CẬP NHẬT (Fix lỗi Admin không thấy lời mời)
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập.');
            header('Location: /index.php?action=login'); exit;
        }
        $page_title = 'Danh sách Bạn bè';
        $friends = getFriendList($db, $current_user_id);
        
        // LẤY LỜI MỜI MÌNH NHẬN (INCOMING REQUESTS)
        // Admin (ID 1) luôn là user1_id, nên query cũ bị lỗi.
        // Sửa: Lấy tất cả các yêu cầu pending mà mình là một bên tham gia (user1_id HOẶC user2_id),
        // và xác định người gửi là bên kia.
        $pending_requests_query = $db->query("
             SELECT f.user1_id, f.user2_id
             FROM friends f
             WHERE (f.user1_id = {$current_user_id} OR f.user2_id = {$current_user_id}) 
               AND f.status = 'pending'
        ");
        
        $incoming_requests = [];
        while($req = $pending_requests_query->fetchArray(SQLITE3_ASSOC)) {
             // Lấy ID của người còn lại trong cặp
             $sender_id = ($req['user1_id'] === $current_user_id) ? $req['user2_id'] : $req['user1_id'];
             
             // Nếu mình là người gửi (sender_id = user2_id), bỏ qua (Đã gửi lời mời)
             if ($req['user1_id'] === $current_user_id) continue;
             
             // Nếu mình là người nhận (receiver), thêm vào danh sách incoming
             $sender_data = $db->querySingle("SELECT id, username, avatar_filename FROM users WHERE id = {$sender_id}", true);
             
             if ($sender_data) {
                 $incoming_requests[] = [
                    'id' => $sender_id,
                    'username' => $sender_data['username'],
                    'avatar_url' => getAvatarUrl($db, $sender_id),
                    'user1_id' => $req['user1_id']
                 ];
             }
        }
        
        require_once 'views/friend_list.php'; 
        break;

    case 'friend_action':
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập.');
            header('Location: /index.php?action=login'); exit;
        }
        $receiver_id = (int)($_GET['user'] ?? 0);
        $do = $_GET['do'] ?? '';
        
        if ($receiver_id > 0 && !empty($do)) {
            handleFriendAction($db, $current_user_id, $receiver_id, $do);
            // Sau khi thao tác, chuyển về hồ sơ của người đó
            header('Location: /index.php?action=profile&user=' . $receiver_id);
            exit;
        }
        setFlashMessage('error', 'Lỗi thao tác bạn bè.');
        header('Location: /index.php?action=friend_list'); exit;
        
    case 'dm':
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập.');
            header('Location: /index.php?action=login'); exit;
        }
        $target_user_id = (int)($_GET['user'] ?? 0);
        if ($target_user_id === $current_user_id || $target_user_id === 0) {
            setFlashMessage('error', 'Không thể nhắn tin với chính mình hoặc người dùng không hợp lệ.');
            header('Location: /index.php'); exit;
        }
        
        $target_user = $db->querySingle("SELECT id, username FROM users WHERE id = {$target_user_id}", true);
        if (!$target_user) {
            setFlashMessage('error', 'Người dùng không tồn tại.');
            header('Location: /index.php'); exit;
        }
        
        $conversation = getConversation($db, $current_user_id, $target_user_id);
        $page_title = 'Nhắn tin với ' . htmlspecialchars($target_user['username']);
        require_once 'views/direct_message.php';
        break;

    case 'api_handle_reaction':
        if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập.');
            header('Location: /index.php?action=login'); exit;
        }
        $post_id = (int)($_GET['post_id'] ?? 0);
        $type = $_GET['type'] ?? '';
        if ($post_id > 0 && in_array($type, ['like', 'love', 'haha'])) {
             handleReaction($db, $post_id, $current_user_id, $type);
        }
        header('Location: /index.php'); exit;
        
    case 'request_verify_action':
         if ($current_user_id === 0) {
            setFlashMessage('error', 'Vui lòng đăng nhập.');
            header('Location: /index.php?action=login'); exit;
        }
        submitVerificationRequest($db, $current_user_id);
        header('Location: /index.php?action=profile'); exit;

    case 'admin_verification_queue':
        if ($current_user_id !== 1) {
            setFlashMessage('error', 'Bạn không có quyền truy cập trang này.');
            header('Location: /index.php'); exit;
        }
        $page_title = 'Quản lý Yêu cầu Xác minh';
        $pending_requests = getPendingVerificationRequests($db);
        require_once 'views/admin_verification_queue.php';
        break;

    case 'admin_verify_update':
        if ($current_user_id !== 1) {
            setFlashMessage('error', 'Bạn không có quyền thực hiện thao tác này.');
            header('Location: /index.php'); exit;
        }
        $request_id = (int)($_GET['request_id'] ?? 0);
        $status = $_GET['status'] ?? '';

        if ($request_id > 0 && in_array($status, ['approved', 'rejected'])) {
            updateVerificationRequestStatus($db, $request_id, $status);
        }
        header('Location: /index.php?action=admin_verification_queue');
        exit;
        
    case 'verify_action':
         if ($current_user_id !== 1) {
            setFlashMessage('error', 'Bạn không có quyền thực hiện thao tác này.');
            header('Location: /index.php'); exit;
        }
        $target_user_id = (int)($_GET['user'] ?? 0);
        if ($target_user_id > 0) {
             toggleVerificationStatus($db, $current_user_id, $target_user_id);
        }
        header('Location: /index.php?action=profile&user=' . $target_user_id);
        exit;

    default:
        // Mặc định chuyển về trang chủ (đã xử lý logic đăng nhập/chưa đăng nhập ở case 'home')
        header('Location: /index.php?action=home');
        exit;
}

// Chỉ gọi renderFooter() nếu không phải các action API (để tránh lỗi header sent)
if (strpos($action, '_action') === false) {
    renderFooter();
}
?>