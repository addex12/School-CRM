<?php
include('config.php');
include('session.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    if (empty($_POST["teacher_name"])) {
        $errors[] = "Teacher name is required.";
    }
    if (empty($_POST["teacher_email"])) {
        $errors[] = "Teacher email is required.";
    }
    if (empty($_POST["teacher_subjects"])) {
        $errors[] = "At least one subject is required.";
    }

    if (empty($errors)) {
        $teacher_id = $_POST['teacher_id'];
        $teacher_name = $_POST['teacher_name'];
        $teacher_email = $_POST['teacher_email'];
        $teacher_subjects = implode(',', $_POST['teacher_subjects']);

        $sql = "UPDATE teachers SET name='$teacher_name', email='$teacher_email', subjects='$teacher_subjects' WHERE id='$teacher_id'";
        if (mysqli_query($db, $sql)) {
            echo "Record updated successfully";
        } else {
            echo "Error updating record: " . mysqli_error($db);
        }
    } else {
        foreach ($errors as $error) {
            echo "<p style='color:red;'>$error</p>";
        }
    }
}

$teacher_id = $_GET['id'];
$sql = "SELECT * FROM teachers WHERE id='$teacher_id'";
$result = mysqli_query($db, $sql);
$row = mysqli_fetch_assoc($result);
$subjects = explode(',', $row['subjects']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Teacher</title>
    <script>
        function addSubjectField() {
            var container = document.getElementById("subjects_container");
            var input = document.createElement("input");
            input.type = "text";
            input.name = "teacher_subjects[]";
            container.appendChild(input);
        }
    </script>
</head>
<body>
    <h2>Update Teacher</h2>
    <form method="post" action="">
        <input type="hidden" name="teacher_id" value="<?php echo $row['id']; ?>">
        <p>
            <label for="teacher_name">Name:</label>
            <input type="text" name="teacher_name" id="teacher_name" value="<?php echo $row['name']; ?>">
        </p>
        <p>
            <label for="teacher_email">Email:</label>
            <input type="email" name="teacher_email" id="teacher_email" value="<?php echo $row['email']; ?>">
        </p>
        <p>
            <label for="teacher_subjects">Subjects:</label>
            <div id="subjects_container">
                <?php foreach ($subjects as $subject): ?>
                    <input type="text" name="teacher_subjects[]" value="<?php echo $subject; ?>">
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addSubjectField()">Add Subject</button>
        </p>
        <p>
            <input type="submit" value="Update">
        </p>
    </form>
</body>
</html>
