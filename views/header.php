<?php
// views/header.php
// $user_data và $current_user_id được lấy từ index.php

$is_logged_in = $current_user_id > 0;
$today = date('Y-m-d');
$checked_in_today = $is_logged_in && (($user_data['last_checkin_day'] ?? '') === $today);
$current_coin = $user_data['coin'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'WOW Social' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .navbar { background-color: #ffffff; }
        .post-item { border: none; border-radius: 8px; }
        .chat-box { background-color: #f7f9fa; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light border-bottom mb-4 sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand text-primary fw-bold" href="/index.php"><i class="bi bi-rocket-takeoff-fill"></i> WOW Social</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item me-2 d-flex align-items-center">
                        <span class="badge bg-warning text-dark me-2" title="Số dư Coin">
                            <i class="bi bi-coin me-1"></i> <?= number_format($current_coin) ?> Coin
                        </span>
                        <?php if ($checked_in_today): ?>
                            <button class="btn btn-sm btn-success disabled" title="Đã điểm danh hôm nay">
                                <i class="bi bi-calendar-check"></i> Đã điểm danh
                            </button>
                        <?php else: ?>
                            <div class="btn-group">
                                <a href="/index.php?action=checkin_action" class="btn btn-sm btn-primary" title="Điểm danh thủ công (+10 Coin)">
                                    <i class="bi bi-calendar-plus"></i> Điểm danh
                                </a>
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Thêm tùy chọn</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/index.php?action=checkin_action&auto=true" id="autoCheckinBtn" onclick="return confirm('Bạn có chắc chắn muốn thực hiện Điểm danh Tự động? Thao tác này sẽ tốn 10 Coin.')">
                                        <i class="bi bi-lightning-fill text-danger"></i> Tự động (-10 Coin)
                                    </a></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?= getAvatarUrl($db, $current_user_id) ?>" class="rounded-circle me-1" style="width: 25px; height: 25px; object-fit: cover;" alt="Avatar">
                            <?= htmlspecialchars($user_data['username']) ?>
                            <?php if ($user_data['is_verified'] ?? 0): ?><i class="bi bi-patch-check-fill text-info ms-1 small"></i><?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/index.php?action=profile"><i class="bi bi-person-fill"></i> Hồ sơ của tôi</a></li>
                            <li><a class="dropdown-item" href="/index.php?action=friend_list"><i class="bi bi-people-fill"></i> Quản lý Bạn bè</a></li>
                            
                            <?php if ($current_user_id === 1): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/index.php?action=admin_verification_queue"><i class="bi bi-shield-lock-fill"></i> **ADMIN: Hàng đợi Xác minh**</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/index.php?action=logout"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-primary me-2" href="/index.php?action=login"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-primary" href="/index.php?action=register"><i class="bi bi-person-plus-fill"></i> Đăng ký</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container">
    <?php if (!empty($flash_messages)): ?>
        <?php foreach ($flash_messages as $type => $message): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>