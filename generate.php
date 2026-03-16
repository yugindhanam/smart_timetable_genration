<?php
session_start();
include 'db.php';

$conn->query("DELETE FROM timetable");

$days = ["Monday","Tuesday","Wednesday","Thursday","Friday"];
$periods = range(1,8);

/* ================================
   Validate total periods
================================ */
$totalResult = $conn->query("SELECT SUM(periods_per_week) as total FROM subjects");
$totalData = $totalResult->fetch_assoc();

if($totalData['total'] > 40){
    die("Total periods exceed 40");
}

/* ================================
   Mentoring Fixed Slot
================================ */
$mentoring = $conn->query("SELECT * FROM subjects WHERE subject_name='Mentoring'");
if($mentoring->num_rows > 0){

    $m = $mentoring->fetch_assoc();

    $conn->query("INSERT INTO timetable(day,period_number,subject_id,faculty_id)
                  VALUES('Wednesday',8,'".$m['subject_id']."','".$m['faculty_id']."')");
}

/* ================================
   LAB ALLOCATION FIRST
================================ */

$labs = $conn->query("SELECT * FROM subjects WHERE type='Lab'");

while($lab = $labs->fetch_assoc()){

    $subject_id = $lab['subject_id'];
    $faculty_id = $lab['faculty_id'];
    $required = $lab['periods_per_week'];

    $allocated = 0;

    foreach($days as $day){

        if($allocated >= $required) break;

        /* Check if lab already that day */
        $labCheck = $conn->query("
        SELECT t.* FROM timetable t
        JOIN subjects s ON t.subject_id=s.subject_id
        WHERE t.day='$day' AND s.type='Lab'
        ");

        if($labCheck->num_rows > 0) continue;

        /* Try 6-7-8 */
        $slot = $conn->query("
        SELECT * FROM timetable 
        WHERE day='$day' AND period_number IN (6,7,8)
        ");

        if($slot->num_rows == 0 && ($required-$allocated)>=3){

            $conn->query("
            INSERT INTO timetable(day,period_number,subject_id,faculty_id) VALUES
            ('$day',6,'$subject_id','$faculty_id'),
            ('$day',7,'$subject_id','$faculty_id'),
            ('$day',8,'$subject_id','$faculty_id')
            ");

            $allocated += 3;
            continue;
        }

        /* Try 4-5 */
        $slot2 = $conn->query("
        SELECT * FROM timetable
        WHERE day='$day' AND period_number IN (4,5)
        ");

        if($slot2->num_rows == 0 && ($required-$allocated)>=2){

            $conn->query("
            INSERT INTO timetable(day,period_number,subject_id,faculty_id) VALUES
            ('$day',4,'$subject_id','$faculty_id'),
            ('$day',5,'$subject_id','$faculty_id')
            ");

            $allocated += 2;
        }
    }
}

/* ================================
   THEORY ALLOCATION
================================ */

$subjects = $conn->query("SELECT * FROM subjects WHERE type='Theory'");

while($sub = $subjects->fetch_assoc()){

    if($sub['subject_name']=="Mentoring") continue;

    $subject_id = $sub['subject_id'];
    $faculty_id = $sub['faculty_id'];
    $required = $sub['periods_per_week'];

    $count = 0;

    shuffle($days);

    /* FIRST PASS (1 per day rule) */

    foreach($days as $day){

        if($count >= $required) break;

        shuffle($periods);

        foreach($periods as $p){

            if($count >= $required) break;

            $slot = $conn->query("
            SELECT * FROM timetable
            WHERE day='$day' AND period_number='$p'
            ");

            $daily = $conn->query("
            SELECT * FROM timetable
            WHERE day='$day' AND subject_id='$subject_id'
            ");

            $prev = $conn->query("
            SELECT * FROM timetable
            WHERE day='$day' AND period_number='".($p-1)."'
            AND subject_id='$subject_id'
            ");

            $next = $conn->query("
            SELECT * FROM timetable
            WHERE day='$day' AND period_number='".($p+1)."'
            AND subject_id='$subject_id'
            ");

            $faculty = $conn->query("
            SELECT * FROM timetable
            WHERE day='$day' AND period_number='$p'
            AND faculty_id='$faculty_id'
            ");

            $repeat = $conn->query("
            SELECT * FROM timetable
            WHERE subject_id='$subject_id'
            AND period_number='$p'
            ");

            if(
                $slot->num_rows==0 &&
                $daily->num_rows<1 &&
                $prev->num_rows==0 &&
                $next->num_rows==0 &&
                $faculty->num_rows==0 &&
                $repeat->num_rows==0
            ){

                $conn->query("
                INSERT INTO timetable(day,period_number,subject_id,faculty_id)
                VALUES('$day','$p','$subject_id','$faculty_id')
                ");

                $count++;
                break;
            }
        }
    }

    /* SECOND PASS (allow extra if space available) */

    if($count < $required){

        foreach($days as $day){

            if($count >= $required) break;

            shuffle($periods);

            foreach($periods as $p){

                if($count >= $required) break;

                $slot = $conn->query("
                SELECT * FROM timetable
                WHERE day='$day' AND period_number='$p'
                ");

                $daily = $conn->query("
                SELECT * FROM timetable
                WHERE day='$day' AND subject_id='$subject_id'
                ");

                $prev = $conn->query("
                SELECT * FROM timetable
                WHERE day='$day' AND period_number='".($p-1)."'
                AND subject_id='$subject_id'
                ");

                $next = $conn->query("
                SELECT * FROM timetable
                WHERE day='$day' AND period_number='".($p+1)."'
                AND subject_id='$subject_id'
                ");

                $faculty = $conn->query("
                SELECT * FROM timetable
                WHERE day='$day' AND period_number='$p'
                AND faculty_id='$faculty_id'
                ");

                if(
                    $slot->num_rows==0 &&
                    $daily->num_rows<2 &&
                    $prev->num_rows==0 &&
                    $next->num_rows==0 &&
                    $faculty->num_rows==0
                ){

                    $conn->query("
                    INSERT INTO timetable(day,period_number,subject_id,faculty_id)
                    VALUES('$day','$p','$subject_id','$faculty_id')
                    ");

                    $count++;
                    break;
                }
            }
        }
    }
}

/* ================================
   Redirect
================================ */

header("Location: display.php");
exit();

?>