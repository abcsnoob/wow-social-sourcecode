<?php
// config.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('DB_FILE', 'social_network.sqlite');

// Cấu hình CDN và Avatar
define('CDN_BASE_URL', 'https://cdn-wowsocial.42web.io/');
define('CDN_AVATAR_PATH', 'assets/avatars/'); 
define('DEFAULT_AVATAR', CDN_BASE_URL . CDN_AVATAR_PATH . 'default.png'); 

// CẤU HÌNH UPLOAD MỚI
define('UPLOAD_DIR', __DIR__ . '/uploads/avatars/'); 
// Đặt giới hạn upload tối đa 10MB (10 * 1024 * 1024 bytes)
define('MAX_UPLOAD_SIZE', 10485760);