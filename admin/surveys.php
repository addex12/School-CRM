<?php include('../includes/header.php'); ?>
<h1>Manage Surveys</h1>
<form action="survey_actions.php" method="post">
    <input type="hidden" name="action" value="create">
    <label for="title">Survey Title:</label>
    <input type="text" id="title" name="title" required>
    <label for="target">Target Audience:</label>
    <select id="target" name="target">
        <option value="students">Students</option>
        <option value="teachers">Teachers</option>
        <option value="parents">Parents</option>
    </select>
    <button type="submit">Create Survey</button>
</form>
<table>
    <thead>
        <tr>
            <th>Title</th>
            <th>Target Audience</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <!-- Populate with survey data -->
    </tbody>
</table>
<?php include('../includes/footer.php'); ?>
