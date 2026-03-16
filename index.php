<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Timetable Generator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">


<h2>Smart Timetable Generator</h2>

<form action="add_subject.php" method="post">
    <input type="text" name="subject_name" placeholder="Subject Name" required>
    <input type="number" name="credit" placeholder="Credit" required>

    <select name="type">
        <option value="Theory">Theory</option>
        <option value="Lab">Lab</option>
    </select>

    <input type="number" name="periods" placeholder="Periods Per Week" required>

    <button type="submit">Add Subject</button>
</form>

<form action="generate.php" method="post">
    <button type="submit">Generate Timetable</button>
</form>

<a href="logout.php"><button>Logout</button></a>

</div>

</body>
</html>
