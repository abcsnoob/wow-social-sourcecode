<?php 
// views/avatar_setting.php
// $current_avatar_url được truyền từ index.php
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h2 class="mb-4">Cài đặt Ảnh đại diện</h2>
        <div class="card shadow-lg border-0">
            <div class="card-header bg-warning text-dark py-3">
                <h5 class="mb-0"><i class="bi bi-image me-2"></i> Cập nhật Avatar</h5>
            </div>
            <div class="card-body p-4 text-center">
                
                <h6 class="mb-3">Ảnh đại diện hiện tại:</h6>
                <img src="<?= htmlspecialchars($current_avatar_url) ?>" class="rounded-circle mb-4 border border-3 border-secondary" style="width: 150px; height: 150px; object-fit: cover;" alt="Current Avatar">
                
                <form action="/index.php?action=upload_avatar_action" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="avatarFile" class="form-label text-start d-block">Chọn ảnh mới</label>
                        <input 
                            class="form-control" 
                            type="file" 
                            id="avatarFile" 
                            name="avatar_file" 
                            accept="image/jpeg, image/png, image/gif" 
                            required>
                        <div class="form-text text-start">Chỉ chấp nhận JPEG, PNG hoặc GIF.</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-upload me-2"></i> Tải lên và Cập nhật</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-muted">
                Ảnh đại diện mới sẽ thay thế ảnh cũ sau khi tải lên thành công.
            </div>
        </div>
        
        <div class="mt-4 text-center">
            <a href="/index.php?action=profile" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại Hồ sơ</a>
        </div>
    </div>
</div>