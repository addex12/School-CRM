document.addEventListener('DOMContentLoaded', function() {
    // If there are pending emails, refresh every 30 seconds
    const pendingCount = document.querySelector('.stat.pending .count');
    if (pendingCount && parseInt(pendingCount.textContent) > 0) {
        setInterval(function() {
            fetch('../ajax/get_bulk_email_status.php?id=<?= $bulk_email_id ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update counts
                        document.querySelector('.stat.success .count').textContent = data.success_count;
                        document.querySelector('.stat.pending .count').textContent = data.pending_count;
                        document.querySelector('.stat.error .count').textContent = data.error_count;
                        
                        // Update progress bar
                        const total = data.total_recipients;
                        const successPercent = total > 0 ? (data.success_count / total) * 100 : 0;
                        const errorPercent = total > 0 ? (data.error_count / total) * 100 : 0;
                        
                        document.querySelector('.progress-success').style.width = successPercent + '%';
                        document.querySelector('.progress-error').style.width = errorPercent + '%';
                        
                        // If no more pending, stop refreshing
                        if (data.pending_count === 0) {
                            location.reload();
                        }
                    }
                });
        }, 30000); // 30 seconds
    }
});