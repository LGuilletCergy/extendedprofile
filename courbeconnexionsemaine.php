<?php

require_once('../../config.php');
include ("jpgraph-3.5.0b1/src/jpgraph.php");
include ("jpgraph-3.5.0b1/src/jpgraph_bar.php");
include ("jpgraph-3.5.0b1/src/jpgraph_line.php");
include ("jpgraph-3.5.0b1/src/jpgraph_error.php");

$xaxis = array();
$yaxis = array();
$i = 0;
$id  = optional_param('id', 0, PARAM_INT);

global $USER;

$context = context_system::instance();

if (id == $USER->id || has_capability('local/extendedprofile:viewinfo', $context)) {

    $xaxis = array();
    $yaxis = array();
    $timestampatmidnight = strtotime(date("d-m-Y", time()));
    $endperiod = time();
    $day = 0;
    $loginsql = "SELECT * from {logstore_standard_log} WHERE userid = ? AND action = ?"
            . "AND timecreated >= ? AND timecreated < ? ORDER BY timecreated ASC";

    for ($day == 0; $day < 7; $day ++) {

        $durationonday = 0;

        $listlogins = $DB->get_recordset_sql($loginsql,
                array($id, 'loggedin', $timestampatmidnight, $endperiod));

        foreach ($listlogins as $login) {

            $sqlnextlogin = "SELECT MIN(timecreated) from {logstore_standard_log} "
                    . "WHERE userid = ? AND action = ? "
                    . "AND timecreated > ? AND timecreated < ?";

            $nextlogintime = $DB->get_field_sql($sqlnextlogin,
                    array($id, 'loggedin', $login->timecreated, $endperiod));

            if (isset($nextlogintime)) {

                $sqllastaction = "SELECT MAX(timecreated) from {logstore_standard_log} "
                        . "WHERE userid = ? AND timecreated > ? AND timecreated < ?";

                $lastactiontime = $DB->get_field_sql($sqllastaction,
                        array($id, $login->timecreated, $nextlogintime));
            } else {

                $sqllastaction = "SELECT MAX(timecreated) from {logstore_standard_log} "
                        . "WHERE userid = ? AND timecreated > ? AND timecreated < ?";

                $lastactiontime = $DB->get_field_sql($sqllastaction,
                        array($id, $login->timecreated, $endperiod));
            }

            if (isset($lastactiontime)) {

                $durationonday += $lastactiontime - $login->timecreated;
            } else {

                $durationonday += 900;
            }

            if (!isset($nextlogintime) && isset($lastactiontime)) {

                $durationonday += 900;
            }

            unset($nextlogintime);
            unset($lastactiontime);
        }

        $listlogins->close();

        $yaxis[] = round($durationonday/60);
        $xaxis[] = date("m-d", $timestampatmidnight);

        $endperiod = $timestampatmidnight;
        $timestampatmidnight = $timestampatmidnight - 24 * 3600;
    }

    $orderedyaxis = array();
    $orderedxaxis = array();

    for ($i = 0; $i < 7; $i++) {

        $orderedxaxis[$i] = $xaxis[6-$i];
        $orderedyaxis[$i] = $yaxis[6-$i];
    }


    $graph = new Graph(450,400);
    $graph->img->SetMargin(40,0,40,40);
    $graph->img->SetAntiAliasing();
    $graph->SetScale("textlin");
    $graph->SetShadow();
    $graph->title->SetFont(FF_FONT1,FS_BOLD);

    $graph->yscale->SetGrace(0);

    $p1 = new LinePlot($orderedyaxis);
    $p1->mark->SetType(MARK_FILLEDCIRCLE);
    $p1->mark->SetFillColor("red");
    $p1->mark->SetWidth(4);
    $p1->SetColor("blue");
    $p1->SetCenter();
    $graph->Add($p1);
    $graph->yaxis->title->Set("Temps en minutes");
    $graph->xaxis->SetTickLabels($orderedxaxis);
    $graph->Stroke();

}
