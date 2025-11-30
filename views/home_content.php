<?php 
// views/home_content.php - Dùng hàm renderPost

renderHeader($page_title, $current_user_info); 
?>

<div class="container mt-4">
    <div class="row">
        
        <div class="col-md-7 mx-auto">
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-pencil-square me-2"></i> Tạo Bài Viết Mới</h5>
                    <form action="/index.php?action=create_post_action" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <textarea class="form-control" name="content" rows="3" placeholder="Bạn đang nghĩ gì?"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="media_file" class="form-label small text-muted"><i class="bi bi-image me-1"></i> Đính kèm ảnh (Tối đa 10MB, JPG, PNG, GIF)</label>
                            <input class="form-control form-control-sm" type="file" id="media_file" name="media_file" accept="image/jpeg,image/png,image/gif">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-1"></i> Đăng Bài</button>
                    </form>
                </div>
            </div>
            
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <?php 
                    // Lấy reaction của người dùng hiện tại (cho nút bấm)
                    $my_reaction = $db->querySingle("SELECT reaction_type FROM reactions WHERE post_id = {$post['id']} AND user_id = {$current_user_id}");
                    
                    // SỬ DỤNG HÀM REUSABLE ĐỂ RENDER BÀI VIẾT
                    renderPost($db, $post, $my_reaction, $current_user_id); 
                    ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    Chưa có bài viết nào được đăng. Hãy là người đầu tiên!
                </div>
            <?php endif; ?>

        </div>
        
    </div>
</div>