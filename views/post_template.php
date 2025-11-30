<?php
// views/post_template.php
// $post, $reaction_summary, $comments, $user_reaction, $db, $current_user_id đã có sẵn

$local_time = convertToLocalTime($post['created_at']);
$media_path = !empty($post['media_filename']) ? '/uploads/avatars/' . $post['media_filename'] : null;

$reaction_type = $user_reaction ?? 'none'; 
$reaction_icon = [
    'none' => '<i class="bi bi-hand-thumbs-up"></i> Thích',
    'like' => '<i class="bi bi-hand-thumbs-up-fill"></i> Thích',
    'love' => '<i class="bi bi-heart-fill"></i> Yêu',
    'haha' => '<i class="bi bi-emoji-laughing-fill"></i> Haha',
    'wow' => '<i class="bi bi-emoji-surprise-fill"></i> Wow',
    'sad' => '<i class="bi bi-emoji-frown-fill"></i> Buồn'
][$reaction_type];

$reaction_color_class = [
    'none' => 'btn-outline-secondary',
    'like' => 'text-primary',
    'love' => 'text-danger',
    'haha' => 'text-warning',
    'wow' => 'text-info',
    'sad' => 'text-warning'
][$reaction_type];

// Reaction summary icons
$summary_icons = '';
foreach ($reaction_summary['summary'] as $type => $count) {
    if ($count > 0) {
        $icon_class = [
            'like' => 'bi-hand-thumbs-up-fill text-primary',
            'love' => 'bi-heart-fill text-danger',
            'haha' => 'bi-emoji-laughing-fill text-warning',
            'wow' => 'bi-emoji-surprise-fill text-info',
            'sad' => 'bi-emoji-frown-fill text-warning'
        ][$type];
        $summary_icons .= "<i class=\"bi {$icon_class} me-1\" title=\"{$count} {$type}\"></i>";
    }
}
?>

<div class="card mb-3 shadow-sm post-item" id="post-<?= $post['id'] ?>">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <img src="<?= $post['avatar_url'] ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;" alt="Avatar">
            <div>
                <h6 class="card-title mb-0">
                    <a href="/index.php?action=profile&user=<?= $post['user_id'] ?>" class="text-decoration-none text-primary">
                        <?= htmlspecialchars($post['author_name'] ?? 'Ẩn danh') ?>
                    </a>
                    <?php if ($post['is_verified'] ?? 0): ?> 
                        <i class="bi bi-patch-check-fill text-info ms-1 small" title="Đã xác minh"></i>
                    <?php endif; ?>
                </h6>
                <small class="text-muted"><i class="bi bi-clock"></i> Đăng lúc: <?= $local_time ?></small>
            </div>
        </div>
        
        <p class="card-text mb-3"><?= nl2br(htmlspecialchars($post['content'])) ?></p>

        <?php if ($media_path): ?>
            <div class="text-center mb-3">
                <img src="<?= $media_path ?>" class="img-fluid rounded" alt="Post Media" style="max-height: 400px; object-fit: cover;">
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-2 small border-bottom pb-2">
            <div>
                <span class="reaction-icon-summary"><?= $summary_icons ?></span>
                <span class="text-muted reaction-count-display">
                    <?= $reaction_summary['total'] > 0 ? "{$reaction_summary['total']} Reactions" : '' ?>
                </span>
            </div>
            <a href="#comments-<?= $post['id'] ?>" class="text-muted text-decoration-none">
                <?= $reaction_summary['comments_count'] ?> Bình luận
            </a>
        </div>

        <div class="d-flex justify-content-around mb-3">
            <div class="btn-group dropup">
                <button type="button" class="btn btn-sm <?= $reaction_color_class ?> main-reaction-btn"
                        onclick="handleReactionClick(<?= $post['id'] ?>, '<?= $reaction_type === 'none' ? 'like' : $reaction_type ?>')">
                    <?= $reaction_icon ?>
                </button>
                <button type="button" class="btn btn-sm <?= $reaction_color_class ?> dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Chọn Reaction</span>
                </button>
                <ul class="dropdown-menu p-2">
                    <li><button class="dropdown-item p-1" onclick="handleReactionClick(<?= $post['id'] ?>, 'like')"><i class="bi bi-hand-thumbs-up-fill text-primary me-2"></i> Thích</button></li>
                    <li><button class="dropdown-item p-1" onclick="handleReactionClick(<?= $post['id'] ?>, 'love')"><i class="bi bi-heart-fill text-danger me-2"></i> Yêu</button></li>
                    <li><button class="dropdown-item p-1" onclick="handleReactionClick(<?= $post['id'] ?>, 'haha')"><i class="bi bi-emoji-laughing-fill text-warning me-2"></i> Haha</button></li>
                    <li><button class="dropdown-item p-1" onclick="handleReactionClick(<?= $post['id'] ?>, 'wow')"><i class="bi bi-emoji-surprise-fill text-info me-2"></i> Wow</button></li>
                    <li><button class="dropdown-item p-1" onclick="handleReactionClick(<?= $post['id'] ?>, 'sad')"><i class="bi bi-emoji-frown-fill text-warning me-2"></i> Buồn</button></li>
                </ul>
            </div>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#comments-<?= $post['id'] ?>">
                <i class="bi bi-chat-square-text"></i> Bình luận
            </button>
        </div>

        <div class="collapse" id="comments-<?= $post['id'] ?>">
            <hr>
            <div class="comments-list mb-3" style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($comments)): ?>
                    <p class="text-center text-muted small">Chưa có bình luận nào.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="d-flex mb-2">
                            <img src="<?= $comment['avatar_url'] ?>" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;" alt="Avatar">
                            <div class="bg-light p-2 rounded small flex-grow-1">
                                <a href="/index.php?action=profile&user=<?= $comment['user_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                    <?= htmlspecialchars($comment['author_name']) ?>
                                </a>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                <small class="text-muted"><?= convertToLocalTime($comment['created_at']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form action="/index.php?action=create_comment_action" method="POST">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <div class="input-group">
                    <input type="text" name="content" class="form-control form-control-sm" placeholder="Viết bình luận..." required>
                    <button type="submit" class="btn btn-primary btn-sm">Gửi</button>
                </div>
            </form>
        </div>
    </div>
</div>