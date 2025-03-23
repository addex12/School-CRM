<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 */
function sanitize($data) {
    global $conn;
    return htmlspecialchars(mysqli_real_escape_string($conn, trim($data)));
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function display_error($message){
    echo "<div class='error'>$message</div>";
}

function display_success($message){
    echo "<div class='success'>$message</div>";
}
?>
