<?php 
// views/profile_content.php - Đã CẬP NHẬT: Thêm Mô tả, Bài viết, Logic Hủy kết bạn & Tooltip

$profile_id = $profile_data['id'] ?? 0; 
$is_me = $current_user_id === $profile_id;
$profile_username = htmlspecialchars($profile_data['username'] ?? 'Người dùng không tồn tại');
$profile_avatar_url = $profile_data['avatar_url'] ?? 'default.png';
$tooltip_text = 'Đây là dấu dành cho các tài khoản đã được admin xác minh và bạn có thể tin tưởng vào các tài khoản này'; // <<< TOOLTIP

$has_pending_request = $db->querySingle("SELECT COUNT(*) FROM verification_requests WHERE user_id = {$profile_id} AND status = 'pending'");

renderHeader($page_title, $current_user_info);
?>

<div class="row">
    <div class="col-md-7 mx-auto">
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center">
                <img src="<?= $profile_avatar_url ?>" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" alt="Avatar">
                
                <h3 class="card-title">
                    <?= $profile_username ?>
                    <?php if ($profile_data['is_verified'] ?? 0): ?>
                        <i class="bi bi-patch-check-fill text-info ms-1" title="<?= $tooltip_text ?>"></i>
                    <?php endif; ?>
                </h3>
                <p class="text-muted">Coin: **<?= number_format($profile_data['coin'] ?? 0) ?>**</p>

                
                <?php if (!$is_me && $current_user_id > 0): ?>
                    <?php
                    // Logic nút Friend và DM khi xem hồ sơ người khác 
                    $friend_status = getFriendshipStatus($db, $current_user_id, $profile_id);
                    $button_html = '';

                    if ($friend_status) {
                        if ($friend_status['status'] === 'accepted') {
                            // NÚT HỦY KẾT BẠN
                            $button_html = '<a href="/index.php?action=friend_action&user='.$profile_id.'&do=unfriend" class="btn btn-warning me-2" onclick="return confirm(\'Bạn có chắc chắn muốn hủy kết bạn?\')"><i class="bi bi-person-dash-fill me-1"></i> Hủy kết bạn</a>';
                        } elseif ($friend_status['status'] === 'pending') {
                            // Xác định ai là người gửi (user1_id hay user2_id) để biết là Chấp nhận hay Hủy lời mời
                            $a = min($current_user_id, $profile_id);
                            if ($friend_status['user1_id'] === $a) {
                                 // Current user là user1_id (người gửi), hiển thị Hủy lời mời
                                 $button_html = '<a href="/index.php?action=friend_action&user='.$profile_id.'&do=cancel" class="btn btn-danger me-2"><i class="bi bi-x-circle me-1"></i> Hủy lời mời</a>';
                            } else {
                                // Current user là user2_id (người nhận), hiển thị Chấp nhận
                                $button_html = '<a href="/index.php?action=friend_action&user='.$profile_id.'&do=accept" class="btn btn-success me-2"><i class="bi bi-check-lg me-1"></i> Chấp nhận lời mời</a>';
                            }
                        }
                    } else {
                        $button_html = '<a href="/index.php?action=friend_action&user='.$profile_id.'&do=send" class="btn btn-success me-2"><i class="bi bi-person-plus-fill me-1"></i> Kết bạn</a>';
                    }
                    ?>
                    <div class="d-flex justify-content-center mb-3">
                        <?= $button_html ?> 
                        <a href="/index.php?action=dm&user=<?= $profile_data['id'] ?>" class="btn btn-info text-white">
                            <i class="bi bi-chat-text-fill me-1"></i> Nhắn tin
                        </a>
                    </div>
                <?php endif; ?>
                
                
                <div class="d-flex justify-content-center flex-wrap">
                    <?php if ($is_me): ?>
                        <?php if (!($profile_data['is_verified'] ?? 0) && !$has_pending_request): ?>
                            <a href="/index.php?action=request_verify_action" class="btn btn-primary mt-2 mx-1">
                                <i class="bi bi-patch-check"></i> Gửi Yêu Cầu Xác Minh
                            </a>
                        <?php elseif (!($profile_data['is_verified'] ?? 0) && $has_pending_request): ?>
                             <button class="btn btn-warning mt-2 mx-1 disabled" title="Yêu cầu đang chờ xét duyệt">
                                <i class="bi bi-clock-history"></i> Yêu cầu đang chờ...
                             </button>
                        <?php endif; ?>
                        <a href="/index.php?action=avatar_setting" class="btn btn-outline-secondary btn-sm mt-2 mx-1">Cài đặt ảnh đại diện</a>
                        <a href="/index.php?action=friend_list" class="btn btn-outline-info btn-sm mt-2 mx-1">Xem Danh sách Bạn bè</a>
                    <?php endif; ?>
                    
                    <?php if ($current_user_id === 1 && $current_user_id !== $profile_id): ?>
                        <div class="mt-2 mx-1">
                            <?php if ($profile_data['is_verified'] ?? 0): ?>
                                 <a href="/index.php?action=verify_action&user=<?= $profile_id ?>" class="btn btn-sm btn-danger">
                                    <i class="bi bi-x-octagon"></i> Hủy xác minh (Admin)
                                 </a>
                            <?php else: ?>
                                 <a href="/index.php?action=verify_action&user=<?= $profile_id ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-patch-check"></i> Xác minh thủ công (Admin)
                                 </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                
                <p class="mt-4">
                    Tham gia từ: <?= convertToLocalTime($profile_data['created_at'] ?? 'now') ?>
                </p>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-info-square me-2"></i> Mô Tả Cá Nhân</h5>
            </div>
            <div class="card-body">
                <?php if ($is_me): ?>
                    <form action="/index.php?action=update_description_action" method="POST">
                        <div class="mb-3">
                            <textarea class="form-control" name="description" rows="3" maxlength="500" placeholder="Viết mô tả cá nhân của bạn tại đây..."><?= htmlspecialchars($profile_data['description'] ?? '') ?></textarea>
                            <div class="form-text">Tối đa 500 ký tự.</div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Cập nhật Mô tả</button>
                    </form>
                <?php else: ?>
                    <p class="card-text">
                        <?= nl2br(htmlspecialchars($profile_data['description'] ?? 'Chưa có mô tả.')) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <h4 class="mb-3"><i class="bi bi-journal-text me-2"></i> Bài viết của <?= $profile_username ?></h4>

        <?php if (empty($profile_posts)): ?>
            <div class="alert alert-info text-center">
                <?= $is_me ? 'Bạn chưa có bài viết nào.' : htmlspecialchars($profile_username) . ' chưa có bài viết nào.' ?>
            </div>
        <?php else: ?>
            <?php foreach ($profile_posts as $post): ?>
                <?php 
                // Lấy reaction của người dùng hiện tại
                $my_reaction = $db->querySingle("SELECT reaction_type FROM reactions WHERE post_id = {$post['id']} AND user_id = {$current_user_id}");
                
                // RENDER BÀI VIẾT
                renderPost($db, $post, $my_reaction, $current_user_id); 
                ?>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>