<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 * GitHub: https://github.com/addex12
 */
?>
<style>
    .alert {
        padding: 8px 12px; /* Smaller padding */
        font-size: 0.9rem; /* Reduced font size */
        border-radius: 4px; /* Slightly rounded corners */
    }
    .alert .close {
        font-size: 0.8rem; /* Smaller close button */
        padding: 2px 6px; /* Reduced padding */
        background: #e74c3c; /* Red background for close button */
        color: #fff;
        border-radius: 50%;
        cursor: pointer;
        transition: background 0.2s;
    }
    .alert .close:hover {
        background: #c0392b; /* Darker red on hover */
    }
</style>
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="close" onclick="this.parentElement.style.display='none';">&times;</button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error alert-dismissible">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="close" onclick="this.parentElement.style.display='none';">&times;</button>
    </div>
<?php endif; ?>
