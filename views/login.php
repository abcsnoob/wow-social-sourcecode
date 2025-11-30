<?php 
// views/login.php
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0 mt-5">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0"><i class="bi bi-box-arrow-in-right me-2"></i> Đăng nhập</h4>
            </div>
            <div class="card-body p-4">
                <form action="/index.php?action=login_action" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên người dùng</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">Đăng nhập</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    Chưa có tài khoản? <a href="/index.php?action=register">Đăng ký ngay</a>
                </div>
            </div>
        </div>
    </div>
</div>
