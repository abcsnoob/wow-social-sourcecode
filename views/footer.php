<?php 
// views/footer.php
?>
    </div><footer class="bg-light py-3 mt-5 border-top">
        <div class="container text-center">
            <small class="text-muted">&copy; 2025 WOW SOCIAL. Tái cấu trúc với Bootstrap và AJAX.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
    // ... (Logic AJAX và Check-in)
    $(document).ready(function() {
        
        // --- 1. LOGIC AJAX LIKE/UNLIKE ---
        $(document).on('click', '.like-btn', function(e) {
            e.preventDefault();
            const btn = $(this);
            const postId = btn.data('post-id');

            // Gửi yêu cầu AJAX POST tới API Endpoint
            $.ajax({
                url: '/index.php?action=api_like_post',
                type: 'POST',
                data: { post_id: postId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.action === 'liked') {
                            btn.removeClass('btn-outline-secondary').addClass('btn-danger');
                            btn.find('i').removeClass('bi-heart').addClass('bi-heart-fill');
                            btn.find('.like-text').text(' Đã Thích');
                            btn.data('liked', 'true');
                        } else {
                            btn.removeClass('btn-danger').addClass('btn-outline-secondary');
                            btn.find('i').removeClass('bi-heart-fill').addClass('bi-heart');
                            btn.find('.like-text').text(' Thích');
                            btn.data('liked', 'false');
                        }
                    } else {
                        alert('Lỗi: ' + response.message);
                    }
                },
                error: function() {
                    alert('Lỗi kết nối API.');
                }
            });
        });
        
        // --- 2. LOGIC ĐIỂM DANH TỰ ĐỘNG ---
        const autoCheckinBtn = document.getElementById('autoCheckinBtn');
        if (autoCheckinBtn) {
            autoCheckinBtn.addEventListener('click', function() {
                const confirmed = confirm("Bạn có chắc chắn muốn thực hiện Điểm danh Tự động? Thao tác này sẽ tốn 10 Coin.");

                if (confirmed) {
                    const checkinType = 'daily'; 
                    const autoCheckinUrl = `/index.php?action=checkin_action&type=${checkinType}&auto=true`;
                    window.location.href = autoCheckinUrl;
                }
            });
        }
    });
    </script>
</body>
</html>