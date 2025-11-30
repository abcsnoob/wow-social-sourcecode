<?php
// views/admin_verification_queue.php
// $requests đã có sẵn từ index.php
?>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <h2 class="mb-4 text-danger"><i class="bi bi-shield-lock-fill me-2"></i> ADMIN: Hàng đợi Yêu cầu Xác minh</h2>

        <?php if (empty($requests)): ?>
            <div class="alert alert-success text-center">
                <i class="bi bi-check-circle-fill me-2"></i> Không có yêu cầu xác minh nào đang chờ xử lý.
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <strong><?= count($requests) ?></strong> yêu cầu đang chờ duyệt
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($requests as $request): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <img src="<?= $request['avatar_url'] ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;" alt="Avatar">
                                <div>
                                    <a href="/index.php?action=profile&user=<?= $request['user_id'] ?>" class="fw-bold text-decoration-none">
                                        <?= htmlspecialchars($request['requested_by_name']) ?> (ID: <?= $request['user_id'] ?>)
                                    </a>
                                    <p class="mb-0 small text-muted">Gửi yêu cầu lúc: <?= convertToLocalTime($request['requested_at']) ?></p>
                                </div>
                            </div>
                            <div>
                                <a href="/index.php?action=handle_request_action&request_id=<?= $request['id'] ?>&do=approve" class="btn btn-sm btn-success me-2" onclick="return confirm('Xác nhận DUYỆT yêu cầu xác minh cho người này?')">
                                    <i class="bi bi-patch-check-fill"></i> Duyệt
                                </a>
                                <a href="/index.php?action=handle_request_action&request_id=<?= $request['id'] ?>&do=reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xác nhận TỪ CHỐI yêu cầu xác minh cho người này?')">
                                    <i class="bi bi-x-circle-fill"></i> Từ chối
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>