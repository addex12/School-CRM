document.addEventListener('DOMContentLoaded', function() {
    // PDF Export
    document.getElementById('export-pdf').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Show loading indicator
        const btn = e.target;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass"></i> Generating PDF...';
        btn.disabled = true;
        
        // Configuration for html2pdf
        const element = document.querySelector('.admin-main');
        const opt = {
            margin: 10,
            filename: `survey-results-${chartData.survey.id}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, logging: true },
            jsPDF: { unit: 'mm', format: 'a3', orientation: 'portrait' }
        };
        
        // Generate PDF
        html2pdf().from(element).set(opt).save().then(() => {
            // Restore button
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.style.position = 'fixed';
            alert.style.bottom = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '1000';
            alert.innerHTML = `
                PDF exported successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }, 3000);
        });
    });
    
    // CSV Export confirmation
    document.querySelectorAll('.dropdown-item[href*="export_csv"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to export to CSV?')) {
                e.preventDefault();
            }
        });
    });
});