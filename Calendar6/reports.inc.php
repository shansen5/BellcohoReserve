<?php

require 'Struct.php';

include_once '../sys/config/config.php';

if ( isset($_POST['report']) ) {
    if ( isset($_POST['report']) == 'unitreserverpt' ) {
        $unit = $_POST['unit'];
        $from = $_POST['from'];
        $to = $_POST['to'];
        if ( $unit == 0 )
            $result = unittotalrpt( $dbConnect, $from, $to );
        else
            $result = unitreserverpt( $dbConnect, $unit, $from, $to );
        echo $result;
    }
}

function convertDate( $inDate ) {
    // Convert a date in the form 'JAN-01-2013' to 20130101.
    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
    $token = strtok( $inDate, "-" );
    if ( $token == false ) 
        return -1;
    $fr_month = $token;
    $token2 = strtok( "-" );
    if ( $token2 == false ) 
        return -1;
    $fr_day = $token2;
    $token3 = strtok( "-" );
    if ( $token3 == false ) 
        return -1;
    $fr_year = $token3;
    return (int)$fr_year * 10000 + ($month_index[$fr_month]+1) * 100 + (int)$fr_day;
}

function unitreserverpt( $dbConnect, $unit, $from, $to ) {
    /*
     */
    $month_names = array( "JAN","FEB", "MAR", "APR", "MAY", 
       "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC" );
    $fr_date = convertDate( $from );
    $to_date = convertDate( $to );
    if ( $fr_date == -1 || $to_date == -1 )
        return "Valid from or to date not entered.";

    $result = "<h2>Report for unit " . $unit . "<br/> from " . $from . "<br/> to " . $to . "</h2><table border=2>";
    $result .= '<tr> <th>&nbsp;&nbsp;Unit&nbsp;&nbsp;</th> <th>&nbsp;&nbsp;Reserved By&nbsp;&nbsp;';
    $result .= '</th> <th>&nbsp;&nbsp;Room&nbsp;&nbsp;</th> <th>&nbsp;&nbsp;Date&nbsp;&nbsp;</th>';
    $result .= '<th>&nbsp;&nbsp;Cancel&nbsp;&nbsp;</th></tr>';
    $total = 0;

    $sql0 = "select pu.unit_id as r_unit, cr.name as room, r.day as day, r.month as month, ";
    $sql0 .= "r.year as year, pn.first_name as first, pn.last_name as last, r.cancel_day as cancel_day, ";
    $sql0 .= "r.cancel_month as cancel_month, r.cancel_year as cancel_year ";
    $sql0 .= "from reservations r, person_units pu, person_names pn, common_rooms cr ";
    $sql0 .= "where r.use_type = 'guest' and r.person_id = pu.person_id ";
	$sql0 .= "and pu.type != 'owner-non-resident' ";
    $sql0 .= "and r.room_id = cr.id ";
    $sql0 .= "and pu.end_date is null and pn.person_id = pu.person_id ";
    $sql0 .= "and r.year * 10000 + r.month * 100 + r.day >= " . $fr_date;
    $sql0 .= " and r.year * 10000 + r.month * 100 + r.day <= " . $to_date;
    $sql0 .= " and r.backup = 0 and pn.end_date is null ";
    $sql0 .= "and pu.unit_id = " . $unit; 
    $sql0 .= " order by year, month, day";
    try {
        $rs0 = $dbConnect->query( $sql0 );
        $results = $rs0->fetchAll(PDO::FETCH_ASSOC);
        foreach($results as $rw0) {
             $result .= '<tr>';
             $result .= '<td>' . $rw0['r_unit'] . '</td>';
             $result .= '<td>' . $rw0['first'] . ' ' . $rw0['last'] . '</td>';
             $result .= '<td>' . $rw0['room'] . '</td>';
             $result .= '<td>' . $month_names[ ((int)$rw0['month'])-1 ] . '-' . $rw0['day'] . '-' . $rw0['year'] . '</td>';
             if ( $rw0['cancel_day'] ) {
                $result .= '<td>' . $month_names[ ((int)$rw0['cancel_month'])-1 ] . '-' . $rw0['cancel_day'] . '-' . $rw0['cancel_year'] . '</td>';
             } else {
                $result .= '<td/>';
             }
             $result .= '</tr>';
             $total++;
        }
    } catch(PDOException $ex) {
        echo "Database error" . $ex->getMessage(); //user friendly message
    }
    $result .= '<tr><td colspan="2">Total days</td><td>' . $total . '</td><td>&nbsp;</td>';
    return $result;
}

function unittotalrpt( $dbConnect, $from, $to ) {
    /*
     */
    $fr_date = convertDate( $from );
    $to_date = convertDate( $to );
    if ( $fr_date == -1 || $to_date == -1 )
        return "Valid from or to date not entered.";

    $result = "<h2>Report for all units " . "<br/> from " . $from . "<br/> to " . $to . "</h2><table border=2>";
    $result .= '<tr> <th>&nbsp;&nbsp;Unit&nbsp;&nbsp;</th> <th>&nbsp;&nbsp;Nights&nbsp;&nbsp;</th></tr>';
    $total = 0;

    $sql0 = "select count(*) as count, pu.unit_id as r_unit ";
    $sql0 .= "from reservations r, person_units pu ";
    $sql0 .= "where r.use_type = 'guest' and r.person_id = pu.person_id ";
	$sql0 .= "and pu.type != 'owner-non-resident' ";
    $sql0 .= "and r.cancel_day is null ";
    $sql0 .= "and pu.end_date is null ";
    $sql0 .= "and r.year * 10000 + r.month * 100 + r.day >= " . $fr_date;
    $sql0 .= " and r.year * 10000 + r.month * 100 + r.day <= " . $to_date;
    $sql0 .= " and r.backup = 0 ";
    $sql0 .= "group by r_unit ";
    $sql0 .= "order by r_unit";
    try {
        $rs0 = $dbConnect->query( $sql0 );
        $results = $rs0->fetchAll(PDO::FETCH_ASSOC);
        foreach($results as $rw0) {
             $result .= '<tr>';
             $result .= '<td>' . $rw0['r_unit'] . '</td>';
             $result .= '<td>' . $rw0['count'] . '</td>';
             $result .= '</tr>';
             $total += $rw0['count'];
        }
    } catch(PDOException $ex) {
        echo "Database error" . $ex->getMessage(); //user friendly message
    }

    $result .= '<tr><td>Total days</td><td>' . $total . '</td>';
    return $result;
}

?>
