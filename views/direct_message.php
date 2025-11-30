<?php 
// views/direct_message.php

renderHeader($page_title, $current_user_info); 
?>

<div class="row">
    <div class="col-md-7 mx-auto">
        
        <div class="card shadow-lg chat-box">
            
            <div class="card-header bg-primary text-white d-flex align-items-center">
                <a href="/index.php?action=profile&user=<?= $target_user_id ?>" class="text-white text-decoration-none">
                    <img src="<?= getAvatarUrl($db, $target_user_id) ?>" class="rounded-circle me-3" style="width: 35px; height: 35px; object-fit: cover;" alt="Target Avatar">
                    <h5 class="mb-0 d-inline-block"><?= htmlspecialchars($target_user['username']) ?></h5>
                </a>
            </div>
            
            <div class="card-body message-list" style="height: 60vh; overflow-y: auto;">
                <?php if (empty($conversation)): ?>
                    <div class="text-center text-muted mt-5">
                        <i class="bi bi-chat-dots display-4"></i>
                        <p class="mt-2">Bắt đầu cuộc trò chuyện với <?= htmlspecialchars($target_user['username']) ?>.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversation as $message): 
                        $is_sender = $message['sender_id'] === $current_user_id;
                        $time = convertToLocalTime($message['created_at']);
                        $status = $message['is_read'] ? 'Đã xem' : 'Đã gửi';
                    ?>
                        <div class="d-flex mb-3 <?= $is_sender ? 'justify-content-end' : 'justify-content-start' ?>">
                            <div class="message-bubble p-2 rounded shadow-sm <?= $is_sender ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 75%;">
                                <p class="mb-0"><?= nl2br(htmlspecialchars($message['content'])) ?></p>
                                <div class="text-end small mt-1 <?= $is_sender ? 'text-white-50' : 'text-muted' ?>">
                                    <?= $time ?>
                                    <?php if ($is_sender): ?>
                                        <i class="bi bi-check-all ms-1" title="<?= $status ?>"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="card-footer bg-light">
                <form action="/index.php?action=send_message_action" method="POST" class="input-group">
                    <input type="hidden" name="receiver_id" value="<?= $target_user_id ?>">
                    <textarea class="form-control" name="content" placeholder="Nhập tin nhắn..." rows="1" required></textarea>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-send-fill"></i> Gửi
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</div>

<script>
    // Cuộn xuống cuối cuộc trò chuyện khi trang được tải
    document.addEventListener('DOMContentLoaded', function() {
        const messageList = document.querySelector('.message-list');
        if (messageList) {
            messageList.scrollTop = messageList.scrollHeight;
        }
        
        // Tự động điều chỉnh chiều cao textarea 
        const textarea = document.querySelector('textarea[name="content"]');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
    });
</script>