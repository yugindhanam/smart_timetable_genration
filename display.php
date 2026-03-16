<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
include 'db.php';
?>

<!DOCTYPE html>
<html>
<head>
<title>Weekly Timetable</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h2>SMART TIMETABLE</h2>
<p style="text-align:center;">Class: III Year | Semester: III</p>

<table>
<tr>
<th>Day</th>
<th>8:00-9:00</th>
<th>9:00-09:50</th>
<th>Break</th>
<th>10:10-11:00</th>
<th>11:00-11:50</th>
<th>11:50-12:40</th>
<th>Lunch</th>
<th>1:30-2:20</th>
<th>2:20-3:10</th>
<th>3:10-4:00</th>
</tr>

<?php
$days = ["Monday","Tuesday","Wednesday","Thursday","Friday"];

foreach($days as $day){

    echo "<tr>";
    echo "<td><b>$day</b></td>";

    for($p=1; $p<=8; $p++){

        // Break after period 2
        if($p == 3){
            echo "<td class='break'>BREAK</td>";
        }

        // Lunch after period 5
        if($p == 6){
            echo "<td class='lunch'>LUNCH</td>";
        }

        $query = $conn->query("SELECT subject_name, type 
                               FROM timetable 
                               JOIN subjects 
                               ON timetable.subject_id = subjects.subject_id
                               WHERE day='$day' AND period_number='$p'");

        if($query->num_rows > 0){
            $data = $query->fetch_assoc();

            if($data['type'] == "Lab"){
                echo "<td class='lab'>".$data['subject_name']."</td>";
            } else {
                echo "<td>".$data['subject_name']."</td>";
            }
        } else {
            echo "<td>-</td>";
        }
    }

    echo "</tr>";
}
?>

</table>

<br>
<button onclick="window.print()">Print Timetable</button>
<a href="index.php"><button>Back</button></a>

</div>

</body>
</html>
