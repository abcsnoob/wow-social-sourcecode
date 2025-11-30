<?php
// db.php
// Đã CẬP NHẬT: Thêm cột description vào bảng users

// Đường dẫn mặc định (thường là root của project)
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', 'uploads/avatars/'); 
}
if (!defined('MAX_UPLOAD_SIZE')) {
    define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
}
if (!defined('DEFAULT_AVATAR')) {
    define('DEFAULT_AVATAR', '/assets/default_avatar.png'); 
}

$db_file = 'social_network.sqlite';

try {
    $db = new SQLite3($db_file);
    $db->enableExceptions(true);

    // Bảng users
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY, 
            username TEXT UNIQUE, 
            password TEXT, 
            description TEXT,  
            coin INTEGER DEFAULT 100, 
            last_checkin_day TEXT,
            avatar_filename TEXT,
            is_verified INTEGER DEFAULT 0,
            created_at TEXT 
        )
    ");

    // Bảng posts
    $db->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            content TEXT,
            media_filename TEXT,
            created_at TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Bảng comments
    $db->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY,
            post_id INTEGER,
            user_id INTEGER,
            content TEXT,
            created_at TEXT,
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Bảng reactions (like, love, haha)
    $db->exec("
        CREATE TABLE IF NOT EXISTS reactions (
            id INTEGER PRIMARY KEY,
            post_id INTEGER,
            user_id INTEGER,
            reaction_type TEXT, -- 'like', 'love', 'haha'
            created_at TEXT,
            UNIQUE(post_id, user_id),
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Bảng friends (status: pending, accepted)
    $db->exec("
        CREATE TABLE IF NOT EXISTS friends (
            user1_id INTEGER, -- Luôn là ID nhỏ hơn
            user2_id INTEGER, -- Luôn là ID lớn hơn
            status TEXT DEFAULT 'pending', 
            created_at TEXT,
            PRIMARY KEY (user1_id, user2_id),
            FOREIGN KEY (user1_id) REFERENCES users(id),
            FOREIGN KEY (user2_id) REFERENCES users(id)
        )
    ");
    
    // Bảng messages
    $db->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY,
            sender_id INTEGER,
            receiver_id INTEGER,
            content TEXT,
            is_read INTEGER DEFAULT 0,
            created_at TEXT,
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        )
    ");
    
    // Bảng verification_requests (Admin chỉ có ID=1 mới thấy)
    $db->exec("
        CREATE TABLE IF NOT EXISTS verification_requests (
            id INTEGER PRIMARY KEY,
            user_id INTEGER UNIQUE,
            requested_at TEXT,
            status TEXT DEFAULT 'pending',
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Tạo tài khoản Admin mặc định (ID=1) nếu chưa có
    $admin_user = $db->querySingle("SELECT id FROM users WHERE id = 1", true);
    if (empty($admin_user)) {
        $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO users (id, username, password, description, is_verified, created_at) VALUES (1, 'Admin', '{$hashed_password}', 'Tài khoản Quản trị Viên của WOW Social.', 1, datetime('now'))");
    }

} catch (Exception $e) {
    die("Lỗi kết nối hoặc tạo database: " . $e->getMessage());
}
?>