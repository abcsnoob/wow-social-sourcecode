<?php
// style.php - Chứa các hàm renderHeader và renderFooter

function renderHeader(string $page_title, ?array $user_info): void 
{
    $is_logged_in = $user_info !== null;
    $current_user_id = $is_logged_in ? $user_info['id'] : 0;
    $is_admin = $current_user_id === 1;
    
    // Lấy số lượng lời mời kết bạn đang chờ (chỉ hiển thị cho người dùng)
    $pending_requests_count = 0;
    if ($is_logged_in) {
        global $db; // Sử dụng biến $db đã được khởi tạo
        // Chỉ đếm các yêu cầu mình là người nhận (user2_id)
        $pending_requests_count = $db->querySingle("SELECT COUNT(*) FROM friends WHERE user2_id = {$current_user_id} AND status = 'pending'");
    }
    
    // Lấy số tin nhắn chưa đọc
    $unread_messages_count = 0;
    if ($is_logged_in) {
        global $db;
        $unread_messages_count = $db->querySingle("SELECT COUNT(*) FROM messages WHERE receiver_id = {$current_user_id} AND is_read = 0");
    }
    
    // Kiểm tra yêu cầu xác minh đang chờ (chỉ hiển thị cho Admin)
    $pending_verification_count = 0;
    if ($is_admin) {
        global $db;
        $pending_verification_count = $db->querySingle("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'");
    }
    
    // Lấy trạng thái điểm danh hôm nay
    $can_checkin = false;
    $is_auto_checkin_available = false;
    if ($is_logged_in && $user_info) {
        $today = date('Y-m-d');
        if (($user_info['last_checkin_day'] ?? '') !== $today) {
            $can_checkin = true;
            $is_auto_checkin_available = ($user_info['coin'] ?? 0) >= 10;
        }
    }

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | WOW Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .navbar { background-color: #ffffff; border-bottom: 1px solid #ddd; }
        .post-item { border-radius: 8px; }
        .flash-message { 
            position: fixed; 
            top: 70px; 
            right: 20px; 
            z-index: 1050; 
            min-width: 250px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container-fluid container-md">
        <a class="navbar-brand text-primary fw-bold" href="/index.php"><i class="bi bi-rocket-takeoff-fill me-1"></i> WOW Social</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/index.php"><i class="bi bi-house-door-fill me-1"></i> Trang Chủ</a>
                </li>
                <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <?php if ($can_checkin): ?>
                             <a class="nav-link btn btn-sm btn-outline-success me-2" href="#" id="checkinDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-calendar-check me-1"></i> Điểm Danh
                             </a>
                             <ul class="dropdown-menu" aria-labelledby="checkinDropdown">
                                <li><a class="dropdown-item" href="/index.php?action=checkin_action&type=daily"><i class="bi bi-hand-index me-1"></i> Thủ công (Nhận 10 Coin)</a></li>
                                <li>
                                    <button class="dropdown-item" id="autoCheckinBtn" <?= !$is_auto_checkin_available ? 'disabled' : '' ?>>
                                        <i class="bi bi-robot me-1"></i> Tự động (Trừ 10 Coin)
                                        <?= !$is_auto_checkin_available ? '<span class="text-danger small ms-1">(Không đủ Coin)</span>' : '' ?>
                                    </button>
                                </li>
                             </ul>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary me-2 disabled" title="Đã điểm danh hôm nay">
                                <i class="bi bi-check-lg me-1"></i> Đã Điểm Danh
                            </button>
                        <?php endif; ?>
                    </li>

                    <li class="nav-item me-2">
                        <a class="nav-link position-relative" href="/index.php?action=friend_list">
                            <i class="bi bi-person-heart me-1"></i> Bạn Bè
                            <?php if ($pending_requests_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $pending_requests_count ?>
                                    <span class="visually-hidden">Lời mời mới</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item me-2">
                        <a class="nav-link position-relative" href="/index.php?action=dm&user=<?= $is_admin ? 2 : 1 // Mở chat với Admin (ID 1) hoặc User 2 ?>">
                            <i class="bi bi-chat-dots-fill me-1"></i> Nhắn Tin
                            <?php if ($unread_messages_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $unread_messages_count ?>
                                    <span class="visually-hidden">Tin nhắn mới</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if ($is_admin): ?>
                        <li class="nav-item me-2">
                            <a class="nav-link position-relative text-danger" href="/index.php?action=admin_verification_queue">
                                <i class="bi bi-shield-lock-fill me-1"></i> Admin
                                <?php if ($pending_verification_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $pending_verification_count ?>
                                        <span class="visually-hidden">Yêu cầu xác minh</span>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= $user_info['avatar_url'] ?>" class="rounded-circle me-1" style="width: 24px; height: 24px; object-fit: cover;" alt="Avatar">
                            <?= htmlspecialchars($user_info['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="/index.php?action=profile"><i class="bi bi-person-circle me-2"></i> Hồ sơ của tôi</a></li>
                            <li><a class="dropdown-item" href="/index.php?action=avatar_setting"><i class="bi bi-image me-2"></i> Cài đặt Ảnh đại diện</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><span class="dropdown-item text-muted small">Coin: <?= number_format($user_info['coin']) ?></span></li>
                            <li><a class="dropdown-item text-danger" href="/index.php?action=logout_action"><i class="bi bi-box-arrow-right me-2"></i> Đăng Xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white" href="/index.php?action=login"><i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn btn-outline-primary" href="/index.php?action=register"><i class="bi bi-person-plus-fill me-1"></i> Đăng ký</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($_SESSION['flash_messages'])): ?>
    <?php foreach ($_SESSION['flash_messages'] as $type => $content): ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show flash-message" role="alert">
            <?= htmlspecialchars($content) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
    <?php unset($_SESSION['flash_messages']); // Xóa sau khi hiển thị ?>
<?php endif; ?>

<main class="container py-4">

<?php
}

function renderFooter(): void 
{
?>
</main>

<footer class="bg-white border-top mt-5 p-3 text-center text-muted small">
    WOW Social &copy; 2024. Nền tảng mạng xã hội đơn giản.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Khởi tạo tất cả tooltips trên trang
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Logic Check-in tự động
        const autoCheckinBtn = document.getElementById('autoCheckinBtn');

        if (autoCheckinBtn) {
            autoCheckinBtn.addEventListener('click', function(e) {
                if (e.target.disabled) {
                    return;
                }
                
                const confirmed = confirm("Bạn có chắc chắn muốn thực hiện Điểm danh Tự động? Thao tác này sẽ tốn 10 Coin.");

                if (confirmed) {
                    const autoCheckinUrl = `/index.php?action=checkin_action&type=daily&auto=true`;
                    window.location.href = autoCheckinUrl;
                }
            });
        }
    });
</script>

</body>
</html>
<?php
}
?>