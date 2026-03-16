<?php
session_start();
include 'db.php';

$conn->query("DELETE FROM timetable");

$days = ["Monday","Tuesday","Wednesday","Thursday","Friday"];

$periods = [1,2,3,4,5,6,7,8]; 
// 3 = Break
// 7 = Lunch

// ============================
// FIXED MENTORING
// ============================
$mentoring = $conn->query("SELECT * FROM subjects WHERE subject_name='Mentoring'");

if($mentoring->num_rows > 0){
    $m = $mentoring->fetch_assoc();

    $conn->query("INSERT INTO timetable 
    (day,period_number,subject_id,faculty_id)
    VALUES ('Wednesday',8,'".$m['subject_id']."','".$m['faculty_id']."')");
}

// ============================
// LAB ALLOCATION FIRST
// ============================

$labs = $conn->query("SELECT * FROM subjects WHERE type='Lab'");

while($row = $labs->fetch_assoc()){

    $subject_id = $row['subject_id'];
    $faculty_id = $row['faculty_id'];
    $total = $row['periods_per_week'];

    $allocated = 0;

    foreach($days as $day){

        if($allocated >= $total)
        break;

        // only one lab per day
        $checkLab = $conn->query("SELECT t.* FROM timetable t
        JOIN subjects s ON t.subject_id=s.subject_id
        WHERE t.day='$day' AND s.type='Lab'");

        if($checkLab->num_rows>0)
        continue;

        // try 6,7,8 (3 block)
        if($total - $allocated >=3){

            $slotCheck = $conn->query("SELECT * FROM timetable 
            WHERE day='$day' AND period_number IN(6,7,8)");

            if($slotCheck->num_rows==0){

                $conn->query("INSERT INTO timetable VALUES
                (NULL,'$day',6,'$subject_id','$faculty_id'),
                (NULL,'$day',7,'$subject_id','$faculty_id'),
                (NULL,'$day',8,'$subject_id','$faculty_id')");

                $allocated +=3;
                continue;
            }
        }

        // try 4,5 (2 block)
        if($total - $allocated >=2){

            $slotCheck = $conn->query("SELECT * FROM timetable 
            WHERE day='$day' AND period_number IN(4,5)");

            if($slotCheck->num_rows==0){

                $conn->query("INSERT INTO timetable VALUES
                (NULL,'$day',4,'$subject_id','$faculty_id'),
                (NULL,'$day',5,'$subject_id','$faculty_id')");

                $allocated +=2;
                continue;
            }
        }

    }

}

// ============================
// THEORY ALLOCATION
// ============================

$theory = $conn->query("SELECT * FROM subjects WHERE type='Theory'");

while($row = $theory->fetch_assoc()){

    $subject_id = $row['subject_id'];
    $faculty_id = $row['faculty_id'];
    $periods_per_week = $row['periods_per_week'];

    $count = 0;

    shuffle($days);

    // ========================
    // FORCE DOUBLE PERIOD
    // ========================
    if($periods_per_week >5){

        foreach($days as $day){

            $inserted=0;

            shuffle($periods);

            foreach($periods as $p){

                if($inserted==2)
                break;

                $check=$conn->query("SELECT * FROM timetable
                WHERE day='$day' AND period_number='$p'");

                $prev=$conn->query("SELECT * FROM timetable
                WHERE day='$day' AND period_number='".($p-1)."'
                AND subject_id='$subject_id'");

                $next=$conn->query("SELECT * FROM timetable
                WHERE day='$day' AND period_number='".($p+1)."'
                AND subject_id='$subject_id'");

                $faculty=$conn->query("SELECT * FROM timetable
                WHERE day='$day' AND period_number='$p'
                AND faculty_id='$faculty_id'");

                $repeat=$conn->query("SELECT * FROM timetable
                WHERE subject_id='$subject_id'
                AND period_number='$p'");

                if($check->num_rows==0 &&
                   $prev->num_rows==0 &&
                   $next->num_rows==0 &&
                   $faculty->num_rows==0 &&
                   $repeat->num_rows==0){

                    $conn->query("INSERT INTO timetable
                    (day,period_number,subject_id,faculty_id)
                    VALUES('$day','$p','$subject_id','$faculty_id')");

                    $inserted++;
                    $count++;
                }
            }

            if($inserted==2)
            break;
        }
    }

    // ========================
    // NORMAL ALLOCATION
    // ========================
    foreach($days as $day){

        if($count >= $periods_per_week)
        break;

        shuffle($periods);

        foreach($periods as $p){

            if($count >= $periods_per_week)
            break;

            $check=$conn->query("SELECT * FROM timetable
            WHERE day='$day' AND period_number='$p'");

            $daily=$conn->query("SELECT * FROM timetable
            WHERE day='$day' AND subject_id='$subject_id'");

            $prev=$conn->query("SELECT * FROM timetable
            WHERE day='$day' AND period_number='".($p-1)."'
            AND subject_id='$subject_id'");

            $next=$conn->query("SELECT * FROM timetable
            WHERE day='$day' AND period_number='".($p+1)."'
            AND subject_id='$subject_id'");

            $faculty=$conn->query("SELECT * FROM timetable
            WHERE day='$day' AND period_number='$p'
            AND faculty_id='$faculty_id'");

            $repeat=$conn->query("SELECT * FROM timetable
            WHERE subject_id='$subject_id'
            AND period_number='$p'");

            if($check->num_rows==0 &&
               $daily->num_rows<2 &&
               $prev->num_rows==0 &&
               $next->num_rows==0 &&
               $faculty->num_rows==0 &&
               $repeat->num_rows==0){

                $conn->query("INSERT INTO timetable
                (day,period_number,subject_id,faculty_id)
                VALUES('$day','$p','$subject_id','$faculty_id')");

                $count++;
                break;
            }

        }

    }

}

// ============================
// REDIRECT
// ============================

header("Location: display.php");
exit();

?>