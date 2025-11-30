<?php 
// views/create_post_form.php

$current_user_id_for_avatar = $user_data['id'] ?? 0;
$user_avatar = getAvatarUrl($db, $current_user_id_for_avatar); 
?>
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form action="/index.php?action=create_post_action" method="POST" enctype="multipart/form-data"> 
            <div class="d-flex align-items-center mb-3">
                <img src="<?= $user_avatar ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;" alt="Avatar">
                <textarea 
                    class="form-control" 
                    id="postContent" 
                    name="content" 
                    rows="3" 
                    placeholder="Bạn đang nghĩ gì, <?= htmlspecialchars($user_data['username'] ?? 'Thành viên') ?>?" 
                    ></textarea>
            </div>
            
            <div class="mb-3 ps-5 ms-3">
                <label for="postMedia" class="form-label">Tải ảnh lên (Max 10MB)</label>
                <input 
                    class="form-control form-control-sm" 
                    type="file" 
                    id="postMedia" 
                    name="post_media" 
                    accept="image/*">
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-fill me-1"></i> Đăng bài
                </button>
            </div>
        </form>
    </div>
</div>