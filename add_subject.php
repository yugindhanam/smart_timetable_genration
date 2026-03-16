<?php
include 'db.php';

$name = $_POST['subject_name'];
$credit = $_POST['credit'];
$type = $_POST['type'];
$periods = $_POST['periods'];

$sql = "INSERT INTO subjects (subject_name, credit, type, periods_per_week)
        VALUES ('$name', '$credit', '$type', '$periods')";

if ($conn->query($sql)) {
    echo "Subject Added Successfully! <br>";
    echo "<a href='index.php'>Go Back</a>";
} else {
    echo "Error: " . $conn->error;
}
?>
