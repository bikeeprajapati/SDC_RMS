        </div>
    </main>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
    // Mark notification as read
    function markNotificationRead(notificationId) {
        fetch('ajax/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI to reflect read status
                const badge = document.querySelector('.badge');
                if (badge) {
                    const count = parseInt(badge.textContent) - 1;
                    if (count <= 0) {
                        badge.remove();
                    } else {
                        badge.textContent = count;
                    }
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Mobile sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        const toggler = document.querySelector('.navbar-toggler');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (toggler && sidebar && mainContent) {
            toggler.addEventListener('click', function() {
                sidebar.classList.toggle('d-none');
                mainContent.style.marginLeft = sidebar.classList.contains('d-none') ? '0' : '240px';
            });
        }
    });
    </script>
</body>
</html> 