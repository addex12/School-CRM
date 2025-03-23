<?php include('../includes/header.php'); ?>
<h1>Communications & Chat Setup</h1>
<form action="communications_actions.php" method="post">
    <input type="hidden" name="action" value="email_config">
    <label for="smtp_server">SMTP Server:</label>
    <input type="text" id="smtp_server" name="smtp_server" required>
    <label for="smtp_port">SMTP Port:</label>
    <input type="text" id="smtp_port" name="smtp_port" required>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Save Email Configuration</button>
</form>
<form action="communications_actions.php" method="post">
    <input type="hidden" name="action" value="telegram_config">
    <label for="bot_token">Telegram Bot Token:</label>
    <input type="text" id="bot_token" name="bot_token" required>
    <button type="submit">Save Telegram Configuration</button>
</form>
<?php include('../includes/footer.php'); ?>
