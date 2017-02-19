<?php


// 
function CH_reserve( $dbConnect ) 
{
    static $MSG_guest = 'Reservation Accepted<br/>';
    static $MSG_guest_error = 'Reservation not accepted<br/>';
    static $MSG_backup = 'You are on the backup list for the requested days.<br/>';
    static $MSG_backup_error = 'Unable to add to the backup list<br/>';
    static $MSG_rejected = 'Reservation not accepted<br/>';
    static $MSG_accepted = 'Reservation accepted<br/>';

    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
                
    $person = '';
    $result = '';
    $cancel = 0;
    $person_id = 0;
    $now_array = getdate();

    if ( isset( $_POST['cancel'] )) {
        if ( isset( $_POST['backup'])) {
            return $MSG_rejected . ' Cancel and backup together are not valid.';
        }
        $cancel = 1;
    }
    if ( isset($_POST['person_id']) && $_POST['person_id'] != 'None' 
            && isset($_POST['password'])) {
        $person_id = (int)$_POST['person_id'];
        $result = $person;
        $password = '';
        $sql = "SELECT password FROM people WHERE id = " . $person_id;
        try {
            $rs = $dbConnect->query( $sql );
            $results = $rs->fetchAll(PDO::FETCH_ASSOC);
            $found = 0;
            foreach($results as $rw) {
                if ( $rw['password'] == $_POST['password'] ) {
                    $found = 1;
                    break;
                }
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
        if ( $found == 0 )
            return $MSG_rejected . 'Password is not valid. ' ;
    } else {
        return 'Please enter a reservation or cancellation.';
    }

    $to_date = array( 'day'=>'0', 'month'=>'JAN', 'year'=>'1900' );
    $fr_date = array( 'day'=>'0', 'month'=>'JAN', 'year'=>'1900' );
    
    $fr_day = $fr_month = $fr_year = $to_day = $to_month = $to_year = '';
    if ( isset( $_POST['from_date'] )) {
        $token = strtok( $_POST['from_date'], "-" );
        if ( $token == false ) 
            return $MSG_rejected . 'No <b>From</b> month entered.';
        $fr_date['month'] = $token;
        $token2 = strtok( "-" );
        if ( $token2 == false ) 
            return $MSG_rejected . 'No <b>From</b> day entered.';
        $fr_date['day'] = $token2;
        $token3 = strtok( "-" );
        if ( $token3 == false ) 
            return $MSG_rejected . 'No <b>From</b> year entered.';
        $fr_date['year'] = $token3;
        // Do not allow reservation, cancellation or backup in the past.
        if (( $now_array['year'] > $fr_date['year'] ) || (( $now_array['year'] == $fr_date['year'] ) && 
            (( $now_array['mon'] - 1 > $month_index[$fr_date['month']] ) || 
                (( $now_array['mon'] - 1 == $month_index[$fr_date['month']] ) &&
               ( $now_array['mday'] > $fr_date['day'] ))))) {
            return $MSG_rejected . 'Date is in the past.';
        }
    } else
        return $MSG_rejected . 'No <b>From</b> date entered.';
    if ( isset( $_POST['to_date'] ) && $_POST['to_date'] != '' ) {
        $token = strtok( $_POST['to_date'], "-" );
        if ( $token == false ) 
            return $MSG_rejected . 'No <b>To</b> month entered.';
        $to_date['month'] = $token;
        $token2 = strtok( "-" );
        if ( $token2 == false ) 
            return $MSG_rejected . 'No <b>To</b> day entered.';
        $to_date['day'] = $token2;
        $token3 = strtok( "-" );
        if ( $token3 == false ) 
            return $MSG_rejected . 'No <b>To</b> year entered.';
        $to_date['year'] = $token3;
        $count = count_days( $dbConnect, $fr_date, $to_date );
    } else {
        // OK if there's no 'to' date.  Just assume a single day.
        $count = 1;
    }
    if ( $count == -1 ) {
        return $MSG_rejected . 'Dates do not make sense.';
    }
    
    if ( isset($_POST['guest_rooms'])) {
        // Go through the requested rooms and see if they can all be reserved.
        // Succeed or fail as a transaction.
        try {
            $dbConnect->beginTransaction();
            $success = true;
            $result = '';
            $guest_rooms = $_POST['guest_rooms'];
            foreach ($_POST['guest_rooms'] as $room_id ) {
                if ( isset( $_POST['backup'] )) {
                    $count_reserved = count_reserved_days( $dbConnect, $fr_date, $to_date, $room_id );
                    if ( $count > $count_reserved ) {
                        return $MSG_rejected . 'Some days requested for backup are available to reserve.';
                    }
                    set_backup( $dbConnect, $room_id, $person_id, $fr_date, $to_date );
                } else {
                    $success = reserve_guest_room( $dbConnect, $room_id, $cancel, $person_id, 
                        $count, $fr_date, $to_date, $result );
                    if ( ! $success ) {
                        break;
                    }
                }
            } 
            if ( $success ) {
                $dbConnect->commit();
            } else {
                $dbConnect->rollBack();
                return $result;
            }
            if ( isset( $_POST['backup'] )) {
                return $MSG_backup . $result;
            } else {
                if ( count( $guest_rooms ) > 1 ) {
                    $result .= " (" . count($guest_rooms) . " rooms )";
                }
                return $result;
            }
        } catch (PDOException $ex) {
            $dbConnect->rollBack();
            if ( isset( $_POST['backup'] )) {
                return $MSG_backup_error;
            } else {
                return $MSG_guest_error;
            }
        }
    }
    if ( isset($_POST['mtg_rooms'])) { 
        $mtg_rooms = $_POST['mtg_rooms'];
        $is_series = false;
        $series_id = 0;
        $cancel_series = false;
        $from_time = $_POST['from_time'];
        if ( isset( $_POST['cancel_series'] )) {
            $cancel_series = true;
        }
        if ( $cancel == 1 || $cancel_series ) {
            $success = cancel_meeting_rooms( $dbConnect, $cancel_series, $mtg_rooms, 
                    $person_id, $fr_date, $from_time, $result );
            return $result;
        }
        // Go through the repeat_wom[] and repeat_dow[] arrays to repeat
        // reservations until the until_date is reached.
        // Start at the from_date.
        // Go through the requested rooms and see if they can all be reserved.
        // Succeed or fail as a transaction.
        $mtg_rooms = $_POST['mtg_rooms'];
        $is_series = false;
        $series_id = 0;
        try {
            $dbConnect->beginTransaction();
            $success = true;
            $result = '';
            if ( isset( $_POST['repeat_dow'] ) && isset( $_POST['repeat_wom'] ) 
                    && isset( $_POST['until_date'] ) && count( $_POST['repeat_dow'] ) > 0
                    && count( $_POST['repeat_wom'] ) > 0 && strlen($_POST['until_date']) > 0 ) {
                $series_id = get_series_id( $dbConnect );
                $repeat_dow = $_POST['repeat_dow'];
                $repeat_wom = $_POST['repeat_wom'];
                $until_date = new DateTime( $_POST['until_date']);
                $success = reserve_series( $dbConnect, $mtg_rooms, $person_id, $series_id, $repeat_dow, 
                                          $repeat_wom, $fr_date, $until_date, $result );
            } else {
                $cur_date = new DateTime ( 
                        $fr_date['year'] . '-' . $fr_date['month'] . '-' .$fr_date['day'] );
                $success = reserve_mtg_rooms( $dbConnect, $mtg_rooms, $person_id,
                         $cur_date, $series_id, $result );                
            }
            if ( $success ) {
                $dbConnect->commit();
            } else {
                $dbConnect->rollBack();
                return $result;
            }
            if ( isset( $_POST['backup'] )) {
                return $MSG_backup . $result;
            } else {
                return $result;
            }
        } catch (PDOException $ex) {
            $dbConnect->rollBack();
            if ( isset( $_POST['backup'] )) {
                return $MSG_backup_error;
            } else {
                return $MSG_guest_error;
            }
        }
    } else {
        // It is an event that doesn't need a room reservation.
        return post_event( $dbConnect, $cancel, $person_id,
                $_POST['committee_id'], $fr_date, $_POST['from_time'], $to_date, $_POST['to_time'],
                $_POST['purpose_id'], addslashes( $_POST['description']));
    }  
        
}

function post_event( $dbConnect, $cancel, $person_id,
        $committee_id, $fr_date, $from_time, $to_date, $to_time, $purpose_id,
        $description)
{
    static $MSG_accepted = 'Reservation Accepted<br/>';
    static $MSG_cancelled = 'Cancellation Accepted<br/>';
    static $MSG_rejected = 'Reservation not accepted<br/>';

    // 
    $reservation_id = 0;
    if ( $cancel == 1 ) {
        if ( cancel_event( $dbConnect, $person_id, $fr_date, $from_time, $to_date ) < 0 ) {
            return $MSG_rejected . 'Cancellation did not succeed.<br/>' . 'Ask to have it processed manually.';
        } else {
            return $MSG_cancelled;
        }
    }
    
    $comment_id = get_comment_id( $dbConnect, $description );
    
    if ( reserve_event( $dbConnect, $person_id, $committee_id, $fr_date, 
            $from_time, $to_date, $to_time, $purpose_id, $comment_id ) < 0 ) {
        return $MSG_rejected . 'Could not accept reservation.<br/>' .
                'Ask for help. (Reserving event.)<br/>';
    }
    return $MSG_accepted;
}  

function get_series_id( $dbConnect ) 
{
    $sql = "SELECT max(series_id) AS max_id FROM reservations";
    try {
        $rs = $dbConnect->query( $sql );
        $results = $rs->fetch(PDO::FETCH_ASSOC);
        if ( !$results || intval( $results['max_id'] ) == 0 ) {
            $series_id = 1;            
        } else {
            $series_id = $results['max_id'] + 1;
        }
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    } 
    return $series_id;
}
 
function reserve_series( $dbConnect, $mtg_rooms, $person_id, $series_id, $repeat_dow, $repeat_wom, 
                        $fr_date, $until_date, &$result )
{
    // for each day of the week (repeat_dow)
    //   move to the first instance of that day on or after from_date
    //   if that day is among the list of week of the month (repeat_wom)
    //     reserve all mtg_rooms for that day
    //     move forward a week at a time to the next week of the month
    //     stop if new date is greater than until_date
    $week_interval = new DateInterval('P7D');
    $day_interval = new DateInterval('P1D');
    foreach ( $_POST['repeat_dow'] as $r_dow ) {
        // logToFile( 'reserve.log', 'Looking at DoW: ' . $r_dow );
        $cur_date = new DateTime ( 
            $fr_date['year'] . '-' . $fr_date['month'] . '-' .$fr_date['day'] );
        // logToFile( 'reserve.log', 'Current date: ' . $cur_date->format( 'd-m-Y' ));
        while ( $cur_date <= $until_date ) {
            $cur_dow = $cur_date->format('D');
            if ( $r_dow == $cur_dow ) {
                // Get the number of the day within the current month
                // See if this is a number matching one of repeat_wom[]
                // If yes, reserve
                // Increment $cur_date by a week
                $day_of_month = $cur_date->format('j');
                $wom = (int)floor( (int)($day_of_month - 1) / (int)7 ) + 1;
                // logToFile( 'reserve.log', '$wom=' . $wom . ', $day_of_month=' . $day_of_month );
                foreach( $repeat_wom as $r_wom ) {
                    if ( $wom == $r_wom ) {
                        // logToFile( 'reserve.log', 'DoW found. Reserving for ' . $cur_date->format( 'd-m-Y' ));
                        $success = reserve_mtg_rooms( $dbConnect, $mtg_rooms, $person_id,
                                            $cur_date, $series_id, $result );  
                        break;
                    }
                }
                $cur_date->add( $week_interval );
            } else {
                $cur_date->add( $day_interval );
            }
        }
    }
    return $success;
}

function reserve_mtg_rooms( $dbConnect, $mtg_rooms, $person_id,
        $c_date, $series_id, &$result )
{
    $cur_date  = array( 'day' => $c_date->format('j'), 
        'month' => $c_date->format('M'), 
        'year' => $c_date->format('Y'));

    foreach( $mtg_rooms as $mtg_room_id ) {
        $success = reserve_meeting_room( $dbConnect, $mtg_room_id, $person_id, 
                $_POST['committee_id'], $cur_date, $_POST['from_time'], $_POST['to_time'],
                $_POST['purpose_id'], addslashes( $_POST['description'] ), 
                $series_id, $result );
    }
    if ( ! $success ) {
        return false;
    }
    return true;
}

function cancel_meeting_rooms( $dbConnect, $cancel_series, $mtg_rooms,
        $person_id, $fr_date, $from_time, &$result )
{
    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
                
   // To cancel a series of meeting room reservations, use the person_id,
    // meeting room, current date and time to identify one reservation
    // from the series.  Get the series_id for that reservation.
    // Cancel all reservations in the series that are on or later than the
    // current date and time. 
    // We could have multiple meeting rooms in the same series, and can't know
    // which one is identified.
    if ( $cancel_series ) {
        foreach( $mtg_rooms as $mtg_room_id ) {
            $success = false;
            $sql = "SELECT series_id FROM reservations WHERE room_id = ";
            $sql .= $mtg_room_id;
            $sql .= " AND person_id = " . $person_id;
            $sql .= " AND (year * 10000 + month * 100 + day) = ";
            $sql .= strval( $fr_date['year'] * 10000 + 
                    ( $month_index[$fr_date['month']] + 1 ) * 100 + $fr_date['day'] );
            $sql .= " AND start_time = " . $from_time . " AND cancel_day is null ";
            try {
                $rs = $dbConnect->query( $sql );
                $results = $rs->fetch(PDO::FETCH_ASSOC);
                if ( $results ) {
                    $series_id =  intval( $results['series_id'] ); 
                    $success = true;
                    break;
                }
            } catch ( PDOException $ex ) {
                $result = "Database error" . $ex->getMessage(); //user friendly message
                return false;
            }
        }
        if ( ! $success ) {
            $result = 'Could not find series. <br/>Unable to cancel.';
            return false;
        } 
        $now_array = getdate();
        $sql4 = "UPDATE reservations SET cancel_day = " . 
                $now_array['mday'] . " , cancel_month = " . $now_array['mon'] . 
                " , cancel_year = " . $now_array['year'];
        $sql4 .= " WHERE series_id = " . $series_id;
        $sql4 .= " AND (year * 10000 + month * 100 + day) >= ";
        $sql4 .= strval( $fr_date['year'] * 10000 + 
                ( $month_index[$fr_date['month']] + 1 ) * 100 + $fr_date['day'] );
        try {
            $rs = $dbConnect->query( $sql4 );
            if ( ! $rs ) {
                 $result = 'Cancellation did not succeed.<br/>' . 
                         'Reservation id = ' . $reservation_id . 
                         '<br/> Ask to have it processed manually.'; 
                 return false;
            }
        } catch (PDOException $ex) {
            $result = "Database error" . $ex->getMessage(); //user friendly message
            return false;
        }
        $result = "All reservations in the series<br/>were cancelled. " . $result;
    } else {    // if ($cancel_series)
        foreach( $mtg_rooms as $mtg_room_id ) {
            $success = cancel_meeting_room( $dbConnect, $mtg_room_id, $person_id,
                    $fr_date, $from_time, $result );  
            if ( ! $success ) {
                return false;
            }
        }
        $result = "The reservation was cancelled. " . $result;
    }
    return true;
}

function cancel_meeting_room( $dbConnect, $mtg_room_id, $person_id, $fr_date,
        $from_time, &$result ) 
{
    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );

    $sql = "SELECT id AS reservation_id FROM reservations WHERE room_id = ";
    $sql .= $mtg_room_id;
    $sql .= " AND person_id = " . $person_id;
    $sql .= " AND (year * 10000 + month * 100 + day) = ";
    $sql .= strval( $fr_date['year'] * 10000 + 
            ( $month_index[$fr_date['month']] + 1 ) * 100 + $fr_date['day'] );
    $sql .= " AND start_time = " . $from_time . " AND cancel_day is null ";
    try {
        $rs = $dbConnect->query( $sql );
        $results = $rs->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;
        foreach($results as $rw) {
            $reservation_id = $rw['reservation_id'];
            if ( $reservation_id > 0 ) {
                $now_array = getdate();
                $sql = "UPDATE reservations SET cancel_day = " . 
                        $now_array['mday'] . " , cancel_month = " . $now_array['mon'] . 
                        " , cancel_year = " . $now_array['year'];
                $sql .= " WHERE id = " . $reservation_id;
                try {
                    $rs = $dbConnect->query( $sql );
                    if ( ! $rs ) {
                         $result = 'Cancellation did not succeed.<br/>' . 
                                 'Reservation id = ' . $reservation_id . 
                                 '<br/> Ask to have it processed manually.'; 
                         return false;
                    }
                } catch (PDOException $ex) {
                    $result = "Database error" . $ex->getMessage(); //user friendly message
                    return false;
                }
                $count++;
            } 
        }
    } catch(PDOException $ex) {
        $result = "Database error" . $ex->getMessage(); //user friendly message
        return false;
    }
    if ( $count == 0 ) {
        $result = 'Cancellation did not succeed.<br/>' . 
            'Ask to have it processed manually.<br/>'; 
        return false;
    }
    $result = "Meeting room cancelled.";
    return true;
}

function reserve_meeting_room( $dbConnect, $mtg_room_id, $person_id,
        $committee_id, $fr_date, $from_time, $to_time, $purpose_id,
        $description, $series_id, &$result )
{
    static $MSG_accepted = 'Reservation Accepted<br/>';
    static $MSG_cancelled = 'Cancellation Accepted<br/>';
    static $MSG_rejected = 'Reservation not accepted<br/>';
    $month_index = array( "Jan"=>0, "Feb"=>1, "Mar"=>2, "Apr"=>3, "May"=>4, 
       "Jun"=>5, "Jul"=>6, "Aug"=>7, "Sep"=>8, "Oct"=>9, "Nov"=>10, "Dec"=>11 );

    // If cancelling, look for the meeting room, reserved by the person
    // (or for the committee) on fr_date starting at from_time.
    // Update the reservation to indicate cancelled.Screenshot from 2015-05-13 15:40:12
    // 
    // Otherwise...
    // See if the meeting room is available for the date and hours.
    // Make the reservation if so.
    $reservation_id = 0;
    if ( $to_time == 0 ) {
            $result = $MSG_rejected . 'Must have an ending time.<br/>';
            return false;
    }
    // Check for conflict in two parts:
    // 1.  A guest room reservation.  This is ok, as long as this meeting
    //     ends before 7 pm.
    // 2.  Another meeting that overlaps.
    // Recognize a guest room reservation as one with start_time null.
    $found_count = 0;
    $sql = "SELECT count(*) AS found_count FROM reservations WHERE  room_id = ";
    $sql .= $mtg_room_id . " AND (year * 10000 + month * 100 + day) = ";
    $sql .= strval( $fr_date['year'] * 10000 + 
            ( $month_index[$fr_date['month']] + 1 ) * 100 + $fr_date['day'] );
    $sql .= " AND start_time is null";
    $sql .= " AND cancel_day is null ";
    try {
        $rs = $dbConnect->query( $sql );
        $results = $rs->fetchAll(PDO::FETCH_ASSOC);
        foreach($results as $rw) {
            if ( $found_count ) {
                // The room is reserved as a guest room.
                // Reject as conflict if this meeting's end_time is after 7 pm.
                if ( $to_time > 19 ) {
                    $result = $MSG_rejected . 'Schedule conflict.  Guest room use.<br/>'; 
                    return false;
                }
            } else {
                // Check for time overlaps of other meetings.
                $sql2a = "SELECT count(*) AS found_count2a FROM reservations WHERE  room_id = ";
                $sql2a .= $mtg_room_id . " AND (year * 10000 + month * 100 + day) = ";
                $sql2a .= strval( $fr_date['year'] * 10000 + 
                    ( $month_index[$fr_date['month']] + 1 ) * 100 + $fr_date['day'] );
                $sql2a .= " AND start_time is not null";
                $sql2a .= " AND ((start_time >= $from_time AND start_time < $to_time)";
                $sql2a .= " OR (end_time > $from_time AND end_time <= $to_time)";
                $sql2a .= " OR (start_time < $from_time && end_time > $to_time ))";
                $sql2a .= " AND cancel_day is null ";
                try {
                    $rs = $dbConnect->query( $sql2a );
                    $results = $rs->fetch(PDO::FETCH_ASSOC);
                    if ( intval( $results['found_count2a'] ) != 0 ) {
                        $result = $MSG_rejected . 'Schedule conflict. Another meeting.<br/>'; 
                        $result .= $fr_date['month'] . ' - ' . $fr_date['day'] . "<br/>";
                        return false;
                    }
                } catch( PDOException $ex ) {
                    $result = "Database error" . $ex->getMessage(); //user friendly message
                    return false;
                }
            }
        }
    } catch(PDOException $ex) {
        $result = "Database error" . $ex->getMessage(); //user friendly message
        return false;
    }
    
    $comment_id = get_comment_id( $dbConnect, $description );
    $now_array = getdate();
    $m = $month_index[$fr_date['month']]+1;
    $sql3 = "INSERT into reservations ( person_id, committee_id, room_id, use_type,
                     day, month, year, start_time, end_time, reserve_day, 
                     reserve_month, reserve_year, purpose_id, comment_id, series_id ) 
                     VALUES ( " . $person_id . ", " . $committee_id . ", " .
                     $mtg_room_id . ", 'meeting', " . $fr_date['day'] . ", " . 
                     $m . ", " . $fr_date['year'] . ", " . 
                     $from_time . ", " . $to_time . ", " . $now_array['mday'] . ", " . 
                     $now_array['mon'] . ", " . $now_array['year'] . ", " . $purpose_id . 
                     ", " . $comment_id . ", " . $series_id .  ")"; 
    try {
        $rs = $dbConnect->query( $sql3 );
        if ( ! $rs ) {
             $result = $MSG_rejected . 'Could not accept reservation.<br/>' .
                'Ask for help.<br/>';
             return false;
        }
    } catch( PDOException $ex ) {
        $result = "Database error" . $ex->getMessage(); //user friendly message  
        return false;
    }
    $result = $MSG_accepted;
    return true;
}

function reserve_guest_room( $dbConnect, $room_id, $cancel, $person_id, $count, 
        $fr_date, $to_date, &$result )
{
    static $MSG_rejected = 'Reservation not accepted<br/>';
    static $MSG_accepted = 'Reservation accepted<br/>';
    static $MSG_cancelled = 'Cancellation Accepted<br/>';

    // Check if the person's unit has enough credits remaining for these days.
    // Credits are for a calendar year.
    if ( $cancel == 0 && check_credits( $dbConnect, $count, $person_id, $fr_date['year'] ) < 0 ) {
        $result =  $MSG_rejected . 'The Unit does not have sufficient credits for this reservation.';
        return false;
    }

    if ( $cancel == 0 ) {
        // Check if the room requested is available for these days.
        // Insert reservation records for these days.
        if ( reserve_room( $dbConnect, $room_id, $person_id, $fr_date, $to_date ) < 0 ) {
                $result = $MSG_rejected . 'The room is not available for these dates.';
                return false;
        }
        $result = $MSG_accepted;
    } else {
        if ( cancel_room( $dbConnect, $room_id, $person_id, $fr_date, $to_date, $count ) < 0 ) {
            $result = $MSG_rejected . 'Cancellation did not succeed.<br/>' . 'Ask to have it processed manually.';
            return false;
        }
        $result = $MSG_cancelled;
    }

    $result = $result . 'Arriving:  ' . $fr_date['month'] . '-' . $fr_date['day'];
    $result .= '-' . $fr_date['year'] . '<br/>';
    if ( $to_date['day'] > 0 ) {
        $result = $result . 'Departing:  ' . $to_date['month'] . '-' . $to_date['day'];
        $result .= '-' . $to_date['year'] . '<br/>';
    }
    // $result .= 'Departing:  ' . $to_month . '-' . $to_day . '-' $to_year . '<br/>';
    $result .= $count . ' nights.';
    return true;
}

function set_backup( $dbConnect, $room_id, $person_id, $fr_date, $to_date )
{
    static $MSG_backup = 'You are on the backup list for the requested days.<br/>';
    static $MSG_backup_error = 'Unable to add to the backup list<br/>';

    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
    $days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
    $next_month = array( "JAN"=>"FEB", "FEB"=>"MAR", "MAR"=>"APR", "APR"=>"MAY",
       "MAY"=>"JUN", "JUN"=>"JUL", "JUL"=>"AUG", "AUG"=>"SEP", "SEP"=>"OCT",
       "OCT"=>"NOV", "NOV"=>"DEC", "DEC"=>"JAN" );

    // Adjust for leap year.  Because of rule about only allowing < 1 month 
    // if either $m1 or $m2 is FEB, we know $y1 == $y2.
    if ( ( $fr_date['month'] == 'FEB' || $to_date['month'] == 'FEB' ) && 
            $fr_date['year'] % 4 == 0 )
        $days_in_month[1] = 29;

    if ( $to_date['day'] == 0 ) {
        // This means no to_day was provided.  One day backup requested.
        $to_date['year'] = $fr_date['year'];
        if ( $fr_date['day'] == $days_in_month[$month_index[$fr_date['month']]] ) {
            $to_date['day'] = 1;
            if ( $fr_date['month'] == 'DEC' ) {
                $to_date['month'] = 'JAN';
                $to_date['year'] = $fr_date['year'] + 1;                     
            } else {
                $to_date['month'] = $next_month[$fr_date['month']];
            }
        } else {
            $to_date['month'] = $fr_date['month'];
            $to_date['day'] = $fr_date['day'] + 1;
        }
    }

    $d = $fr_date['day'];
    $m = $month_index[ $fr_date['month'] ] + 1;
    $y = $fr_date['year'];
    $now_array = getdate();
    while ( $y <= $to_date['year'] ) {
        if (( $y < $to_date['year'] ) || ( $m < ( $month_index[ $to_date['month'] ] + 1 ))) {
            while ( $d <= $days_in_month[ $m - 1 ] ) {
                // If the person already has the room reserved or is backup for 
                // this date, however that happened, just skip.
                $found_count = 0;
                $sql = "SELECT count(*) AS found_count FROM reservations WHERE "
                     . "room_id = " .  $room_id . " AND year = " . $y . " and month = "
                     . $m . " and day = " . $d . " and person_id = " . $person_id
                     . " and cancel_day is null";
                try {
                    $rs = $dbConnect->query( $sql );
                    $results = $rs->fetch(PDO::FETCH_ASSOC);
                    if ( intval( $results['found_count'] ) == 0 ) {
                        continue;            
                    }
                } catch(PDOException $ex) {
                    throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
                }

                $sql = "INSERT into reservations ( person_id, room_id, day, 
                     month, year, reserve_day, reserve_month, reserve_year,
                     backup ) 
                     VALUES ( " . $person_id . ", " . $room_id . ", " . $d . ", " . 
                     $m . ", " . $y . ", " . $now_array['mday'] . ", " . 
                     $now_array['mon'] . ", " . $now_array['year'] . ", 1 )"; 

                try {
                    $rs = $dbConnect->query( $sql );
                    if ( ! $rs ) {
                         return $MSG_backup_error . 'Could not accept reservation.<br/>' .
                            'Ask for help.<br/>';
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                $d++;
            }    
            if ( $m == 12 ) {
                $m = 1;
                $y++;
            } else
                $m = $m + 1;
            $d = 1;
        } 
        if ( $m == ( $month_index[ $to_date['month'] ] + 1 )) {
            // From and to are in the same month.
            while ( $d < $to_date['day'] ) {
                // If the person already has the room reserved or is backup for 
                // this date, however that happened, just skip.
                $found_count = 0;
                $sql = "SELECT count(*) AS found_count FROM reservations WHERE "
                     . "room_id = " .  $room_id . " AND year = " . $y . " and month = "
                     . $m . " and day = " . $d . " and person_id = " . $person_id
                     . " and cancel_day is null";
                try {
                    $rs = $dbConnect->query( $sql );
                    $results = $rs->fetch(PDO::FETCH_ASSOC);
                    $found_count = intval( $results['found_count']);
                    if ( $found_count == 0 ) {
                        continue;            
                    }
                } catch(PDOException $ex) {
                    throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
                }
                $sql = "INSERT into reservations ( person_id, room_id, day, 
                     month, year, reserve_day, reserve_month, reserve_year,
                     backup ) 
                     VALUES ( " . $person_id . ", " . $room_id . ", " . $d . ", " . 
                     $m . ", " . $y . ", " . $now_array['mday'] . ", " . 
                     $now_array['mon'] . ", " . $now_array['year'] . ", 1 )"; 
                try {
                    $rs = $dbConnect->query( $sql );
                    if ( ! $rs ) {
                         return $MSG_backup_error . 'Could not accept reservation.<br/>' .
                            'Ask for help.<br/>';
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                $d++;
            }    
        }
        $y++;
        $d = 1;
    }
    return $MSG_backup;    
}

// Count how many days in the period were reserved.  If it isn't all the days,
// we can't put in the user as a backup.
function count_reserved_days( $dbConnect, $fr_date, $to_date, $room_id ) 
{
    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
    $days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
    $next_month = array( "JAN"=>"FEB", "FEB"=>"MAR", "MAR"=>"APR", "APR"=>"MAY",
       "MAY"=>"JUN", "JUN"=>"JUL", "JUL"=>"AUG", "AUG"=>"SEP", "SEP"=>"OCT",
       "OCT"=>"NOV", "NOV"=>"DEC", "DEC"=>"JAN" );

    // Adjust for leap year.  Because of rule about only allowing < 1 month 
    // if either $m1 or $m2 is FEB, we know $y1 == $y2.
    if ( ( $fr_date['month'] == 'FEB' || $to_date['month'] == 'FEB' ) && 
            $fr_date['year'] % 4 == 0 )
        $days_in_month[1] = 29;

    if ( $to_date['day'] == 0 ) {
        // This means no to_day was provided.  One day reservation requested.
        $to_date['year'] = $fr_date['year'];
        if ( $fr_date['day'] == $days_in_month[$month_index[$fr_date['month']]] ) {
            $to_date['day'] = 1;
            if ( $fr_date['month'] == 'DEC' ) {
                $to_date['month'] = 'JAN';
                $to_date['year'] = $fr_date['year'] + 1;                     
            } else {
                $to_date['month'] = $next_month[$fr_date['month']];
            }
        } else {
            $to_date['month'] = $fr_date['month'];
            $to_date['day'] = $fr_date['day'] + 1;
            
        }
    }
    $found_count = 0;
    $sql = "SELECT count(*) AS found_count FROM reservations WHERE room_id = ";
    $sql .= $room_id . " AND (year * 10000 + month * 100 + day) >= ";
    $sql .= strval( $fr_date['year'] * 10000 + 
            ( $month_index[$fr_date['month']] + 1 ) * 100 + $fr_date['day'] );
    $sql .= " AND (year * 10000 + month * 100 + day ) < ";
    $sql .= strval( $to_date['year'] * 10000 + 
            ( $month_index[$to_date['month']] + 1 ) * 100 + $to_date['day'] );
    $sql .= " AND cancel_day IS NULL AND backup = 0";
    try {
        $rs = $dbConnect->query( $sql );
        $results = $rs->fetch(PDO::FETCH_ASSOC);
        $found_count = intval( $results['found_count']);
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    }
    return  $found_count;
}    

// Only allow one month wrap-around.  We don't allow reserving more than
// a few weeks at most.
function count_days( $dbConnect, $fr_date, $to_date ) 
{
    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
    $days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

    // Adjust for leap year.  Because of rule about only allowing < 1 month 
    // if either $m1 or $m2 is FEB, we know $y1 == $y2.
if ( ( $fr_date['month'] == 'FEB' || $to_date['month'] == 'FEB' ) && 
            $fr_date['year'] % 4 == 0 )
        $days_in_month[1] = 29;

    if ( $to_date['day'] == '' ) 
        return 1;
    if ( $fr_date['year'] > $to_date['year'] ) return -1;
    if ( $fr_date['year'] == $to_date['year'] && 
            $month_index[$fr_date['month']] > $month_index[$to_date['month']] )
        return -1;
    if ( intval($to_date['year']) - intval($fr_date['year']) > 1 ) return -1;
    if ( intval($to_date['year']) - intval($fr_date['year']) == 1 ) {
        if ( $to_date['month'] != 'JAN' || $fr_date['month'] != 'DEC' ) return -1;
        return ( 31 - intval( $fr_date['day'] )) + intval( $to_date['day'] ); 
    }
    if ( $fr_date['month'] == $to_date['month'] && $fr_date['year'] == $to_date['year'] ) {
        if ( $fr_date['day'] > $to_date['day'] )
            return -1;
        if ( $fr_date['day'] == $to_date['day'] )
            return 1;
        return intval( $to_date['day'] - $fr_date['day'] );
    }
    $im1 = $month_index[$fr_date['month']];
    $im2 = $month_index[$to_date['month']];
    if ( $im2 - $im1 > 1 ) return -1;
    $mi1 = $month_index[$fr_date['month']];
    $idm1 = $days_in_month[$mi1];
    
    return $idm1 - intval($fr_date['day']) + intval($to_date['day']) ;
}    

function check_credits( $dbConnect, $count, $person_id, $year )
{
    $credits_used = 0;
    $unit_id = 0;
    
    $sql = 'select id as unit_id from units where id in ';
    $sql .= '(select unit_id from person_units where person_id = ' . $person_id . ' and end_date is null)';
    try {
        $rs = $dbConnect->query( $sql );
        $results = $rs->fetch(PDO::FETCH_ASSOC);
        $unit_id = intval( $results['unit_id'] );
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    }
    
    $sql2 = 'SELECT count(*) AS credits_used FROM reservations WHERE person_id in ';
    $sql2 .= '(select person_id from person_units where unit_id = ';
    $sql2 .= $unit_id . ' AND end_date is null) AND cancel_day is null AND year = ' . $year;
    $sql2 .= ' AND use_type = "guest" and backup = 0';
    try {
        $rs2 = $dbConnect->query( $sql2 );
        $results2 = $rs2->fetch(PDO::FETCH_ASSOC);
        $credits_used = intval( $results2['credits_used'] );
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    }
    
    $guest_limit = 0;
    $sql3 = "SELECT guest_limit FROM units WHERE id = " . $unit_id;
    try {
        $rs3 = $dbConnect->query( $sql3 );
        $results3 = $rs3->fetch(PDO::FETCH_ASSOC);
        $guest_limit = intval( $results3['guest_limit'] );
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    }
    
    return  $guest_limit - $credits_used - $count;
}

function reserve_room( $dbConnect, $room_id, $person_id, $fr_date, $to_date )
{
    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
    $days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
    $next_month = array( "JAN"=>"FEB", "FEB"=>"MAR", "MAR"=>"APR", "APR"=>"MAY",
       "MAY"=>"JUN", "JUN"=>"JUL", "JUL"=>"AUG", "AUG"=>"SEP", "SEP"=>"OCT",
       "OCT"=>"NOV", "NOV"=>"DEC", "DEC"=>"JAN" );

    // Adjust for leap year.  Because of rule about only allowing < 1 month 
    // if either $m1 or $m2 is FEB, we know $y1 == $y2.
    if ( ( $fr_date['month'] == 'FEB' || $to_date['month'] == 'FEB' ) && 
            $fr_date['year'] % 4 == 0 )
        $days_in_month[1] = 29;

    if ( $to_date['day'] == 0 ) {
        // This means no to_day was provided.  One day reservation requested.
        $to_date['year'] = $fr_date['year'];
        if ( $fr_date['day'] == $days_in_month[$month_index[$fr_date['month']]] ) {
            $to_date['day'] = 1;
            if ( $fr_date['month'] == 'DEC' ) {
                $to_date['month'] = 'JAN';
                $to_date['year'] = $fr_date['year'] + 1;                     
            } else {
                $to_date['month'] = $next_month[$fr_date['month']];
            }
        } else {
            $to_date['month'] = $fr_date['month'];
            $to_date['day'] = $fr_date['day'] + 1;
            
        }
    }
    $found_count = 0;
    $sql = "SELECT count(*) AS found_count FROM reservations WHERE room_id = ";
    $sql .= $room_id . " AND (year * 10000 + month * 100 + day) >= ";
    $sql .= strval( $fr_date['year'] * 10000 + 
            ( $month_index[$fr_date['month']] + 1 ) * 100 + $fr_date['day'] );
    $sql .= " AND (year * 10000 + month * 100 + day ) < ";
    $sql .= strval( $to_date['year'] * 10000 + 
            ( $month_index[$to_date['month']] + 1 ) * 100 + $to_date['day'] );
    $sql .= " AND cancel_day IS NULL";
    try {
        $rs = $dbConnect->query( $sql );
        $results = $rs->fetch(PDO::FETCH_ASSOC);
        $found_count = intval( $results['found_count'] );
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    }
    if ( $found_count > 0 ) {
        return -1;
    }

    // Check for time overlaps of other meetings.
    $found_count2a = 0;
    $sql2a = "SELECT count(*) AS found_count2a FROM reservations WHERE  room_id = ";
    $sql2a .= $room_id . " AND (year * 10000 + month * 100 + day) = ";
    $sql2a .= strval( $fr_date['year'] * 10000 + 
            ( $month_index[$fr_date['month']] + 1 ) * 100 + $fr_date['day'] );
    $sql2a .= " AND start_time is not null";
    $sql2a .= " AND end_time >= 19";
    $sql2a .= " AND cancel_day is null ";
    try {
        $rs2a = $dbConnect->query( $sql2a );
        $results2a = $rs2a->fetch(PDO::FETCH_ASSOC);
        $found_count2a = intval( $results2a['found_count2a'] );
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    }
    if ( $found_count2a ) {
        return -1;            
    }

    if ( $to_date['day'] == 0 ) {
        if ( $fr_date['day'] == $days_in_month[ $month_index[ $fr_date['month'] ]] ) {
            $to_date['day'] = 1;
            $to_date['month'] = $next_month[ $fr_date['month'] ];
            if ( $fr_date['month'] == 'DEC' )
                $to_date['year'] = $fr_date['year'] + 1;
        } else {
            $to_date['day'] = $fr_date['day'] + 1;
            $to_date['month'] = $fr_date['month'];
            $to_date['year'] = $fr_date['year'];
        }
    }
    $d = $fr_date['day'];
    $m = $month_index[ $fr_date['month'] ] + 1;
    $y = $fr_date['year'];
    $now_array = getdate();
    while ( $y <= $to_date['year'] ) {
        if (( $y < $to_date['year'] ) || ( $m < ( $month_index[ $to_date['month'] ] + 1 ))) {
            while ( $d <= $days_in_month[ $m - 1 ] ) {
                $sql = "INSERT into reservations ( person_id, room_id, day, 
                     month, year, reserve_day, reserve_month, reserve_year ) 
                     VALUES ( " . $person_id . ", " . $room_id . ", " . $d . ", " . 
                     $m . ", " . $y . ", " . $now_array['mday'] . ", " . 
                     $now_array['mon'] . ", " . $now_array['year'] . " )"; 
                try {
                    $rs = $dbConnect->query( $sql );
                    if ( ! $rs ) {
                         return -1;
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                $d++;
            }    
            if ( $m == 12 ) {
                $m = 1;
                $y++;
            } else
                $m = $m + 1;
            $d = 1;
        } 
        if ( $m == ( $month_index[ $to_date['month'] ] + 1 )) {
            // From and to are in the same month.
            while ( $d < $to_date['day'] ) {
                $sql = "INSERT into reservations ( person_id, room_id, day, 
                     month, year, reserve_day, reserve_month, reserve_year ) 
                     VALUES ( " . $person_id . ", " . $room_id . ", " . $d . ", " . 
                     $m . ", " . $y . ", " . $now_array['mday'] . ", " . 
                     $now_array['mon'] . ", " . $now_array['year'] . " )"; 
                try {
                    $rs = $dbConnect->query( $sql );
                    if ( ! $rs ) {
                         return -1;
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                $d++;
            }    
        }
        $y++;
        $d = 1;
    }
    return 0;
}

function promote_backup( $dbConnect, $room_id, $day, $month, $year )
{
    // Find the earliest reservation with backup flag set.
    // Set the backup flag to 0.
    // Email that user that they now have the reservation.

    $reserve_id = 0;
    $person_id = 0;

    $sql1 = 'select id as reserve_id, person_id from reservations where room_id = ' 
        . $room_id . " and day = " . $day . " and month = " . $month . " and year ="
        . $year . ' and cancel_day is null and backup = 1 order by id';
    
    try {
        $rs1 = $dbConnect->query( $sql1 );
        $results1 = $rs1->fetch(PDO::FETCH_ASSOC);
        $reserve_id = intval( $results1['reserve_id'] );
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    }
    
    if ( $reserve_id > 0 ) {
        $sql2 = 'update reservations set backup = 0 where id = ' . $reserve_id;
        try {
            $rs2 = $dbConnect->query( $sql2 );
            if ( ! $rs2 ) {
                 return -1;
            }
        } catch( PDOException $ex ) {
            echo "Database error" . $ex->getMessage(); //user friendly message        
        }
        
        // Get the first name and email address of the person being promoted
        // for this reservation.  Also the room name.  Send an email:
        //  Dear <name>,
        //  There was a cancellation and you now have <room> reserved on
        //  <date>.
        $first_name = '';
        $sql3 = 'select first_name from person_names where person_id = ' . $person_id;
        try {
            $rs3 = $dbConnect->query( $sql3 );
            $results3 = $rs3->fetch(PDO::FETCH_ASSOC);
            $first_name = $results3['first_name'];
        } catch(PDOException $ex) {
            throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
        }
        
        $room_name = '';
        $sql4 = 'select name as room_name from common_rooms where id = ' . $room_id;
        try {
            $rs4 = $dbConnect->query( $sql4 );
            $results4 = $rs4->fetch(PDO::FETCH_ASSOC);
            $room_name = $results4['room_name'];
        } catch(PDOException $ex) {
            throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
        }
        
        $email = '';
        $sql5 = 'select email from people where id = ' . $person_id;
        try {
            $rs5 = $dbConnect->query( $sql5 );
            $results5 = $rs5->fetch(PDO::FETCH_ASSOC);
            $email = $results5['email'];
        } catch(PDOException $ex) {
            throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
        }
        
        // If size($email) == 0 || size($room_name) == 0 || size($first_name) == 0
        // return error.
        $email .= ", steven.e.hansen@gmail.com";
        $subject = "Guest room reservation";
        $message = "Hello " . $first_name . ".\n";
        $message .= "There was a cancellation and you now have " . $room_name;
        $message .= " reserved on " . $month . "/" . $day . "/" . $year . "\n\n";
        $message .= "Please cancel this reservation if you no longer want the room ";
        $message .= "for that night.";
        $from = "admin@bellcoho.com";
        $headers = "From:" . $from;
        mail($email,$subject,$message,$headers);
      
    }
}

function cancel_room( $dbConnect, $room_id, $person_id, $fr_date, $to_date, $count )
{
    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
    $days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
    $next_month = array( "JAN"=>"FEB", "FEB"=>"MAR", "MAR"=>"APR", "APR"=>"MAY",
       "MAY"=>"JUN", "JUN"=>"JUL", "JUL"=>"AUG", "AUG"=>"SEP", "SEP"=>"OCT",
       "OCT"=>"NOV", "NOV"=>"DEC", "DEC"=>"JAN" );

    // Adjust for leap year.  Because of rule about only allowing < 1 month 
    // if either $m1 or $m2 is FEB, we know $y1 == $y2.
    if ( ( $fr_date['month'] == 'FEB' || $to_date['month'] == 'FEB' ) && 
            $fr_date['year'] % 4 == 0 )
        $days_in_month[1] = 29;

    if ( $to_date['day'] == 0 ) {
        if ( $fr_date['day'] == $days_in_month[ $month_index[ $fr_date['month'] ]] ) {
            $to_date['day'] = 1;
            $to_date['month'] = $next_month[ $fr_date['month'] ];
            if ( $fr_date['month'] == 'DEC' ) {
                $to_date['year'] = $fr_date['year'] + 1;
            } else {
                $to_date['year'] = $fr_date['year'];
            }
        } else {
            $to_date['day'] = $fr_date['day'] + 1;
            $to_date['month'] = $fr_date['month'];
            $to_date['year'] = $fr_date['year'];
        }
    }

    // Make sure reservations exist for this unit and room.  We've already
    // counted the days, so check that the selected count matches.
    $found_count = 0;
    $sql2 = "SELECT count(*) AS found_count FROM reservations WHERE person_id = " .
       $person_id . " AND room_id = " . $room_id . 
       " AND (year * 10000 + month * 100 + day) >= " . 
       strval( $fr_date['year'] * 10000 + ( $month_index[$fr_date['month']] + 1 ) * 100 + 
               $fr_date['day'] ) . " AND (year * 10000 + month * 100 + day ) < " . 
       strval( $to_date['year'] * 10000 + ( $month_index[$to_date['month']] + 1 ) * 100 + 
               $to_date['day'] ) . " AND cancel_day is null ";
    // print $sql . '<br/>';
    try {
        $rs2 = $dbConnect->query( $sql2 );
        $results2 = $rs2->fetch(PDO::FETCH_ASSOC);
        $found_count = intval( $results2['found_count'] );
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    }
    if ( $found_count != $count )
        return -1;
   
    $d = $fr_date['day'];
    $m = $month_index[ $fr_date['month'] ] + 1;
    $y = $fr_date['year'];
    $now_array = getdate();
    while ( $y <= $to_date['year'] ) {
        if (( $y < $to_date['year'] ) || ( $m < $month_index[ $to_date['month'] ] + 1 )) {
            while ( $d <= $days_in_month[ $m - 1 ] ) {
                $backup = 0;
                // First see if the current person's reservation is as a backup.
                // We won't promote another person if the current person is a backup.
                $sql3a = "SELECT backup from reservations WHERE " . 
                    "person_id = " . $person_id . " AND room_id = " . $room_id . 
                    " AND day = " . $d . " AND month = " . $m . 
                    " AND year = " . $y . " AND cancel_day is null"; 
                try {
                    $rs3a = $dbConnect->query( $sql3a );
                    $results3a = $rs3a->fetch(PDO::FETCH_ASSOC);
                    $backup = $results3a['backup'];
                } catch(PDOException $ex) {
                    throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
                }
                $sql3 = "UPDATE reservations SET cancel_day = " . 
                    $now_array['mday'] . " , cancel_month = " . $now_array['mon'] . 
                    " , cancel_year = " . $now_array['year'] . " WHERE  
                    person_id = " . $person_id . " AND room_id = " . $room_id . 
                    " AND day = " . $d . " AND month = " . $m . 
                    " AND year = " . $y . " AND cancel_day is null"; 

                // print '1) ' . $sql . '<br/>';
                try {
                    $rs3 = $dbConnect->query( $sql3 );
                    if ( ! $rs3 ) {
                         return -1;
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                if ( $backup == 0 ) {
                    promote_backup( $dbConnect, $room_id, $d, $m, $y );
                }
                $d++;
            }    
            if ( $m == 12 ) {
                $m = 1;
                $y++;
            } else
                $m = $m + 1;
            $d = 1;
        } 
        if ( $m == ( $month_index[ $to_date['month'] ] + 1 )) {
            // From and to are in the same month.
            while ( $d < $to_date['day'] ) {
                $backup = 0;
                // First see if the current person's reservation is as a backup.
                // We won't promote another person if the current person is a backup.
                $sql3a = "SELECT backup from reservations WHERE " . 
                    "person_id = " . $person_id . " AND room_id = " . $room_id . 
                    " AND day = " . $d . " AND month = " . $m . 
                    " AND year = " . $y . " AND cancel_day is null"; 
                try {
                    $rs3a = $dbConnect->query( $sql3a );
                    $results3a = $rs3a->fetch(PDO::FETCH_ASSOC);
                    $backup = $results3a['backup'];
                } catch(PDOException $ex) {
                    throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
                }
                $sql4 = "UPDATE reservations SET cancel_day = " . 
                    $now_array['mday'] . " , cancel_month = " . $now_array['mon'] . 
                    " , cancel_year = " . $now_array['year'] . " WHERE  
                    person_id = " . $person_id . " AND room_id = " . $room_id . 
                    " AND day = " . $d . " AND month = " . $m . 
                    " AND year = " . $y . " AND cancel_day is null"; 
                // print '2) ' . $sql . '<br/>';
                try {
                    $rs4 = $dbConnect->query( $sql4 );
                    if ( ! $rs4 ) {
                         return -1;
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                if ( $backup == 0 ) {
                    promote_backup( $dbConnect, $room_id, $d, $m, $y );
                }
                $d++;
            }    
        }
        $y++;
        $d = 1;
    }
    return 0;
}

function cancel_event( $dbConnect, $person_id, $fr_date, $fr_time, $to_date )
{
    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
    $days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
    $next_month = array( "JAN"=>"FEB", "FEB"=>"MAR", "MAR"=>"APR", "APR"=>"MAY",
       "MAY"=>"JUN", "JUN"=>"JUL", "JUL"=>"AUG", "AUG"=>"SEP", "SEP"=>"OCT",
       "OCT"=>"NOV", "NOV"=>"DEC", "DEC"=>"JAN" );

    // Adjust for leap year.  Because of rule about only allowing < 1 month 
    // if either $m1 or $m2 is FEB, we know $y1 == $y2.
    if ( ( $fr_date['month'] == 'FEB' || $to_date['month'] == 'FEB' ) && 
            $fr_date['year'] % 4 == 0 )
        $days_in_month[1] = 29;

    if ( $to_date['day'] == 0 ) {
        if ( $fr_date['day'] == $days_in_month[ $month_index[ $fr_date['month'] ]] ) {
            $to_date['day'] = 1;
            $to_date['month'] = $next_month[ $fr_date['month'] ];
            if ( $fr_date['month'] == 'DEC' )
                $to_date['year'] = $fr_date['year'] + 1;
        } else {
            $to_date['day'] = $fr_date['day'] + 1;
            $to_date['month'] = $fr_date['month'];
            $to_date['year'] = $fr_date['year'];
        }
    }

    // Make sure reservations exist for this unit and room.  We've already
    // counted the days, so check that the selected count matches.
    $found_count = 0;
    $sql2 = "SELECT count(*) AS found_count FROM reservations WHERE person_id = " .
       $person_id . 
       " AND (year * 10000 + month * 100 + day) >= " . 
       strval( $fr_date['year'] * 10000 + ( $month_index[$fr_date['month']] + 1 ) * 100 + 
               $fr_date['day'] ) . " AND (year * 10000 + month * 100 + day ) < " . 
       strval( $to_date['year'] * 10000 + ( $month_index[$to_date['month']] + 1 ) * 100 + 
               $to_date['day'] ) . " AND cancel_day is null ";
    error_log( print_r( $sql ), TRUE );
    try {
        $rs2 = $dbConnect->query( $sql2 );
        $results2 = $rs2->fetch(PDO::FETCH_ASSOC);
        $found_count = intval( $results2['found_count'] );
    } catch(PDOException $ex) {
        throw new Exception( "Database error" . $ex->getMessage() ); //user friendly message
    }
    if ( $found_count == 0 )
        return -1;
   
    $d = $fr_date['day'];
    $m = $month_index[ $fr_date['month'] ] + 1;
    $y = $fr_date['year'];
    $now_array = getdate();
    while ( $y <= $to_date['year'] ) {
        if (( $y < $to_date['year'] ) || ( $m < $month_index[ $to_date['month'] ] + 1 )) {
            while ( $d <= $days_in_month[ $m - 1 ] ) {
                $sql3 = "UPDATE reservations SET cancel_day = " . 
                    $now_array['mday'] . " , cancel_month = " . $now_array['mon'] . 
                    " , cancel_year = " . $now_array['year'] . " WHERE  
                    person_id = " . $person_id . 
                    " AND day = " . $d . " AND month = " . $m . 
                    " AND year = " . $y . " AND cancel_day is null"; 

                // print '1) ' . $sql . '<br/>';
                try {
                    $rs3 = $dbConnect->query( $sql3 );
                    if ( ! $rs3 ) {
                         return -1;
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                $d++;
            }    
            if ( $m == 12 ) {
                $m = 1;
                $y++;
            } else
                $m = $m + 1;
            $d = 1;
        } 
        if ( $m == ( $month_index[ $to_date['month'] ] + 1 )) {
            // From and to are in the same month.
            while ( $d < $to_date['day'] ) {
                $sql4 = "UPDATE reservations SET cancel_day = " . 
                    $now_array['mday'] . " , cancel_month = " . $now_array['mon'] . 
                    " , cancel_year = " . $now_array['year'] . " WHERE  
                    person_id = " . $person_id . " AND room_id = " . $room_id . 
                    " AND day = " . $d . " AND month = " . $m . 
                    " AND year = " . $y . " AND cancel_day is null"; 
                // print '2) ' . $sql . '<br/>';
                try {
                    $rs4 = $dbConnect->query( $sql4 );
                    if ( ! $rs4 ) {
                         return -1;
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                $d++;
            }    
        }
        $y++;
        $d = 1;
    }
    return 0;
}

function reserve_event( $dbConnect, $person_id, $committee_id, $fr_date, 
        $from_time, $to_date, $to_time, $purpose_id, $comment_id )
{
    $month_index = array( "JAN"=>0, "FEB"=>1, "MAR"=>2, "APR"=>3, "MAY"=>4, 
       "JUN"=>5, "JUL"=>6, "AUG"=>7, "SEP"=>8, "OCT"=>9, "NOV"=>10, "DEC"=>11 );
    $days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
    $next_month = array( "JAN"=>"FEB", "FEB"=>"MAR", "MAR"=>"APR", "APR"=>"MAY",
       "MAY"=>"JUN", "JUN"=>"JUL", "JUL"=>"AUG", "AUG"=>"SEP", "SEP"=>"OCT",
       "OCT"=>"NOV", "NOV"=>"DEC", "DEC"=>"JAN" );

    // Adjust for leap year.  Because of rule about only allowing < 1 month 
    // if either $m1 or $m2 is FEB, we know $y1 == $y2.
    if ( ( $fr_date['month'] == 'FEB' || $to_date['month'] == 'FEB' ) && 
            $fr_date['year'] % 4 == 0 )
        $days_in_month[1] = 29;

    if ( $to_date['day'] == 0 ) {
        // This means no to_day was provided.  One day reservation requested.
        $to_date['year'] = $fr_date['year'];
        if ( $fr_date['day'] == $days_in_month[$month_index[$fr_date['month']]] ) {
            $to_date['day'] = 1;
            if ( $fr_date['month'] == 'DEC' ) {
                $to_date['month'] = 'JAN';
                $to_date['year'] = $fr_date['year'] + 1;                     
            } else {
                $to_date['month'] = $next_month[$fr_date['month']];
            }
        } else {
            $to_date['month'] = $fr_date['month'];
            $to_date['day'] = $fr_date['day'] + 1;
            
        }
    }
    
    // For a multi-day event, insert a reservation for each day.
    // If the first day has a fr_time, use that, otherwise leave blank.
    // (It will be taken to be an all-day event.)
    // If the last day has a to_time, use that, otherwise leave blank.
    // (It will be taken to be for the full day.)
    // Intermediate days have fr_time and to_time blank.
    // Examples:
    //  start 6 pm Friday through Sunday (no end time)
    //  start Saturday through Sunday (no start or end time)
    //  start Saturday through noon Monday (no start)
    //  start 4 pm Friday through 10 pm Friday
    
    if ( $to_date['day'] == 0 ) {
        $to_date['day'] = $fr_date['day'];
        $to_date['month'] = $fr_date['month'];
        $to_date['year'] = $fr_date['year'];
    }
    $d = $fr_date['day'];
    $m = $month_index[ $fr_date['month'] ] + 1;
    $y = $fr_date['year'];
    $now_array = getdate();
    while ( $y <= $to_date['year'] ) {
        if (( $y < $to_date['year'] ) || ( $m < ( $month_index[ $to_date['month'] ] + 1 ))) {
            while ( $d <= $days_in_month[ $m - 1 ] ) {
                $sql1a = "INSERT into reservations ( person_id, room_id, use_type, committee_id, day, 
                         month, year, reserve_day, reserve_month, reserve_year,
                         purpose_id, comment_id"; 
                $sql1b = "VALUES ( " . $person_id . ", 0, 'event', " . $committee_id . ", " . $d . ", " . 
                         $m . ", " . $y . ", " . $now_array['mday'] . ", " . 
                         $now_array['mon'] . ", " . $now_array['year'] . ", " . $purpose_id .
                         ", " . $comment_id; 
                if ( $d == $fr_date['day'] && $from_time > 0 ) {
                    $sql1a .= ", start_time";
                    $sql1b .= ", " . $from_time;
                } 
                if ( $d == $to_date['day'] && $to_time > 0 ) {
                    $sql1a .= ", end_time";
                    $sql1b .= ", " . $to_time;
                } 
                $sql1 = $sql1a . ") " . $sql1b . ")";
                try {
                    $rs1 = $dbConnect->query( $sql1 );
                    if ( ! $rs1 ) {
                         return -1;
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                $d++;
            }    
            if ( $m == 12 ) {
                $m = 1;
                $y++;
            } else
                $m = $m + 1;
            $d = 1;
        } 
        if ( $m == ( $month_index[ $to_date['month'] ] + 1 )) {
            // From and to are in the same month.
            while ( $d <= $to_date['day'] ) {
                $sql1a = "INSERT into reservations ( person_id, room_id, use_type, committee_id, day, 
                         month, year, reserve_day, reserve_month, reserve_year,
                         purpose_id, comment_id"; 
                $sql1b = "VALUES ( " . $person_id . ", 0, 'event', " . $committee_id . ", " . $d . ", " . 
                         $m . ", " . $y . ", " . $now_array['mday'] . ", " . 
                         $now_array['mon'] . ", " . $now_array['year'] . ", " . $purpose_id .
                         ", " . $comment_id; 
                if ( $d == $fr_date['day'] && $from_time > 0 ) {
                    $sql1a .= ", start_time";
                    $sql1b .= ", " . $from_time;
                } 
                if ( $d == $to_date['day'] && $to_time > 0 ) {
                    $sql1a .= ", end_time";
                    $sql1b .= ", " . $to_time;
                } 
                $sql2 = $sql1a . ") " . $sql1b . ")";
                try {
                    $rs2 = $dbConnect->query( $sql2 );
                    if ( ! $rs2 ) {
                         return -1;
                    }
                } catch( PDOException $ex ) {
                    echo "Database error" . $ex->getMessage(); //user friendly message        
                }
                $d++;
            }    
        }
        $y++;
        $d = 1;
    }
    return 0;
}

function get_comment_id( $dbConnect, $description )
{
    // Put the description into the comments table, if the exact description
    // doesn't already exist.
    $comment_id = -1;
    $sql1 = "SELECT id as comment_id from comments where text = '" . $description . "'";
    try {
        $rs = $dbConnect->query( $sql1 );
        $results = $rs->fetch(PDO::FETCH_ASSOC);
        if ( $results['comment_id'] <= 0 ) {
            $sql2 = "INSERT into comments (text) VALUES ( '" . $description . "' )";
            try {
                $rs = $dbConnect->query( $sql2 );
                if ( ! $rs ) {
                    return -1;
                }
                return $dbConnect->lastInsertId();
            } catch( PDOException $ex ) {
                echo "Database error" . $ex->getMessage(); //user friendly message
            }

        } else {
            return $comment_id;
        }
    } catch(PDOException $ex) {
        echo "Database error" . $ex->getMessage(); //user friendly message
    }
}

function logToFile($filename, $msg) 
{  
   // open file 
   $fd = fopen($filename, "a"); 
   // append date/time to message 
   $str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;  
   // write string 
   fwrite($fd, $str . "\n"); 
   // close file 
   fclose($fd); 
} 

?>
