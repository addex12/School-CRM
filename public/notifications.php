<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 */
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    redirect('index.php');
}
require_once __DIR__ . '/../includes/header.php';
?>
    <div class="container">
        <h1>Notifications</h1>
        <!-- Notifications content -->
    </div>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
