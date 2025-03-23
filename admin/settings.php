<?php
/**
 * Developer: Adugna Gizaw
 * Email: gizawadugna@gmail.com
 * Phone: +251925582067
 * GitHub: https://github.com/addex12
 * LinkedIn: https://www.linkedin.com/in/eleganceict
 * Twitter: https://twitter.com/eleganceict1
 */
include('../includes/header.php'); ?>
<h1>Settings</h1>
<form action="settings_actions.php" method="post">
    <input type="hidden" name="action" value="add_feature">
    <label for="feature_name">Feature Name:</label>
    <input type="text" id="feature_name" name="feature_name" required>
    <button type="submit">Add Feature</button>
</form>
<form action="settings_actions.php" method="post">
    <input type="hidden" name="action" value="add_column">
    <label for="table_name">Table Name:</label>
    <input type="text" id="table_name" name="table_name" required>
    <label for="column_name">Column Name:</label>
    <input type="text" id="column_name" name="column_name" required>
    <button type="submit">Add Column</button>
</form>
<form action="settings_actions.php" method="post">
    <input type="hidden" name="action" value="add_row">
    <label for="table_name_row">Table Name:</label>
    <input type="text" id="table_name_row" name="table_name_row" required>
    <button type="submit">Add Row</button>
</form>
<?php include('../includes/footer.php'); ?>
