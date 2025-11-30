<?php 
// views/friend_list.php

renderHeader($page_title, $current_user_info); 
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <h3 class="mb-4 text-primary"><i class="bi bi-person-heart me-2"></i> <?= htmlspecialchars($page_title) ?></h3>
        
        <ul class="nav nav-tabs mb-3" id="friendTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="accepted-tab" data-bs-toggle="tab" data-bs-target="#accepted" type="button" role="tab" aria-controls="accepted" aria-selected="true">
                    Bạn Bè (<?= count($friends) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">
                    Lời Mời Đang Chờ 
                    <?php if (!empty($incoming_requests)): ?>
                        <span class="badge bg-danger ms-1"><?= count($incoming_requests) ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="friendTabsContent">
            
            <div class="tab-pane fade show active" id="accepted" role="tabpanel" aria-labelledby="accepted-tab">
                <div class="list-group shadow-sm">
                    <?php if (!empty($friends)): ?>
                        <?php foreach ($friends as $friend): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="<?= $friend['avatar_url'] ?>" class="rounded-circle me-3" style="width: 45px; height: 45px; object-fit: cover;" alt="Avatar">
                                    <div>
                                        <a href="/index.php?action=profile&user=<?= $friend['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                            <?= htmlspecialchars($friend['username']) ?>
                                        </a>
                                        <div class="small text-muted">Đã kết bạn</div>
                                    </div>
                                </div>
                                <div>
                                    <a href="/index.php?action=dm&user=<?= $friend['id'] ?>" class="btn btn-sm btn-info text-white me-2">
                                        <i class="bi bi-chat-text"></i> Nhắn tin
                                    </a>
                                    <a href="/index.php?action=friend_action&user=<?= $friend['id'] ?>&do=unfriend" class="btn btn-sm btn-warning" onclick="return confirm('Bạn có chắc chắn muốn hủy kết bạn với <?= htmlspecialchars($friend['username']) ?>?')">
                                        <i class="bi bi-person-dash"></i> Hủy kết bạn
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item text-center text-muted">
                            Bạn chưa có bạn bè nào.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                <div class="list-group shadow-sm">
                    <?php if (!empty($incoming_requests)): ?>
                        <?php foreach ($incoming_requests as $request): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="<?= $request['avatar_url'] ?>" class="rounded-circle me-3" style="width: 45px; height: 45px; object-fit: cover;" alt="Avatar">
                                    <div>
                                        <a href="/index.php?action=profile&user=<?= $request['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                            <?= htmlspecialchars($request['username']) ?>
                                        </a>
                                        <div class="small text-danger">Đã gửi lời mời</div>
                                    </div>
                                </div>
                                <div>
                                    <a href="/index.php?action=friend_action&user=<?= $request['id'] ?>&do=accept" class="btn btn-sm btn-success me-2">
                                        <i class="bi bi-check-lg"></i> Chấp nhận
                                    </a>
                                    <a href="/index.php?action=friend_action&user=<?= $request['id'] ?>&do=cancel" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn từ chối lời mời của <?= htmlspecialchars($request['username']) ?>?')">
                                        <i class="bi bi-x"></i> Từ chối
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item text-center text-muted">
                            Bạn không có lời mời kết bạn nào đang chờ.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div> </div>
</div>