<?php include('../includes/header.php'); ?>
<h1>Manage Users</h1>
<form action="user_actions.php" method="post">
    <input type="hidden" name="action" value="create">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required>
    <label for="role">Role:</label>
    <select id="role" name="role">
        <option value="admin">Admin</option>
        <option value="student">Student</option>
        <option value="teacher">Teacher</option>
        <option value="parent">Parent</option>
    </select>
    <button type="submit">Create User</button>
</form>
<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <!-- Populate with user data -->
    </tbody>
</table>
<?php include('../includes/footer.php'); ?>
