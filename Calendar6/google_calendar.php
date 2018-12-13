<?php
    function get_title( $dbConnect ) {
        $purpose = '';
        $committee_id = $_SESSION['google_event']['committee_id'];
        $committee = null;
        $sql0 = "SELECT name as committee FROM committees WHERE id = " . $committee_id;
        try {
            $rs = $dbConnect->query( $sql0 );
            $results = $rs->fetch(PDO::FETCH_ASSOC);
            if ( $results ) {
                $committee = $results['committee'];
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
        if ( $committee ) {
            $purpose = $committee . ' - ';
        }
        $purpose_id = $_SESSION['google_event']['purpose_id'];
        $sql0 = "SELECT text as purpose FROM purposes WHERE id = " . $purpose_id;
        try {
            $rs0 = $dbConnect->query( $sql0 );
            $results = $rs0->fetch(PDO::FETCH_ASSOC);
            if ( $results ) {
                $purpose .= $results['purpose'];
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
        return $purpose;
    }

    function get_from_date() {
        $from_date = new DateTime( $_SESSION['google_event']['from_date'] );
        $formatted_date = $from_date->format( 'Y-m-d');
        return $formatted_date;
    }
    
    function get_from_time() {
        $from_time = $_SESSION['google_event']['from_time'];
        $from_time = substr_replace( $from_time, ':', strlen( $from_time ) -  2, 0 );
        while ( strlen( $from_time ) < 5 ) {
            $from_time = '0' . $from_time;
        }
        return $from_time;
    }
    
    function get_to_date() {
        $to_date = $_SESSION['google_event']['to_date'];
        if ( empty( $to_date )) {
            return null;
        }
        $to_date = new DateTime( $to_date );
        $formatted_date = $to_date->format( 'Y-m-d');
        return $formatted_date;
    }
    
    function get_to_time() {
        $to_time = $_SESSION['google_event']['to_time'];
        if ( empty( $to_time) ) {
            return null;
        }
        $to_time = substr_replace( $to_time, ':', strlen( $to_time ) -  2, 0 );
        while ( strlen( $to_time ) < 5 ) {
            $to_time = '0' . $to_time;
        }
        return $to_time;
    }
    
    function get_location( $dbConnect ) {
        $location = '';
        $sql0 = "SELECT name FROM common_rooms WHERE ";
        $first_room = true;
        foreach ( $_SESSION['google_event']['mtg_rooms'] as $room_id ) {
            if ( $first_room ) {
                $first_room = false;
            } else {
                $sql0 .= ' OR ';
            }
            $sql0 .= 'id = ' . $room_id;
        }
        try {
            $rs0 = $dbConnect->query($sql0);
            $results = $rs0->fetchAll(PDO::FETCH_ASSOC);
            $first_room = true;
            foreach ($results as $rw0) {
                if ( $first_room ) {
                    $first_room = false;
                } else {
                    $location .= ', ';
                }
                $location .= $rw0['name'];
            }
        } catch (PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
        return $location;
    }

    function get_description( $dbConnect ) {
        $description = '';

        $description .= $_SESSION['google_event']['description'];
        $person_id = $_SESSION['google_event']['person_id'];
        $person = null;
        $sql1 = "SELECT first_name, last_name FROM person_names where end_date is null AND person_id = " . $person_id;
        try {
            $rs = $dbConnect->query( $sql1 );
            $results = $rs->fetch(PDO::FETCH_ASSOC);
            if ( $results ) {
                $person = $results['first_name'] . ' ' . $results['last_name'];
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
        $description .= ' (Submitted by ' . $person . ')';
        $description = trim( $description );
        return $description;
    }
 
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>

    <link rel="stylesheet" type="text/css" media="all" href="css/bccastyle.css"></link>

    <title>Post Meeting/Event to Bellcoho Community Calendar</title>

    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></meta>

</head>

<body>
<?php 
    require_once('controller.php');
    require_once('Utils.php');
    include('vendor/autoload.php');

    if (array_key_exists('cancel', $_POST)) {
        $_SESSION['google_event']['result'] = '';
        header( 'Location: http://www.bellcoho.com/commonhouse/Calendar6/index.php' );
    }
    if ( isset($_GET['from_date']) ) {
        try
        {
            $clientEmail = EVENT_CALENDAR_CLIENT_EMAIL; 

            $scopes = array("https://www.googleapis.com/auth/calendar",
            "https://www.googleapis.com/auth/calendar.readonly");

            $client = new Google_Client();
            $client->setApplicationName('BellCoho Reservations Event');

            $key_file = EVENT_CALENDAR_PRIVATE_KEY;
            $client->setAuthConfig( $key_file );
            $client->setSubject( $clientEmail );
            $client->setScopes( $scopes );

            $service = new Google_Service_Calendar($client);

            $from_date = $_GET[ 'from_date' ];  // Format: mm/dd/yyyy
            $from_time = $_GET[ 'from_time' ];  // Format: HH:MM AM
            $to_date = $_GET[ 'to_date' ];
            $to_time = $_GET[ 'to_time' ];
            $location = $_GET[ 'location' ];
            $title = $_GET[ 'title' ];
            $description = $_GET[ 'description' ];
            
            if ( empty( $to_date )) {
                $to_date = $from_date;
            }
            $dateString = $from_date . ' ' . $from_time;
            $startDate = strftime('%Y-%m-%dT%H:%M:%S', strtotime($dateString));
            $dateString = $to_date . ' ' . $to_time;
            $endDate = strftime('%Y-%m-%dT%H:%M:%S', strtotime($dateString));

            $event = new Google_Service_Calendar_Event();

            $event->setSummary( $title );
            $event->setLocation( $location );
            $event->setDescription( $description );

            //Set the beginning of the event
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($startDate);
            $start->setTimeZone('America/Los_Angeles');
            $event->setStart($start);

            //Set the end of the event
            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($endDate);
            $end->setTimeZone('America/Los_Angeles');
            $event->setEnd($end);

            $createdEvent = $service->events->insert(EVENT_CALENDAR_ACCOUNT,$event);

            $_SESSION['google_event']['result'] = 'Event posted on community calendar';
            header( 'Location: http://www.bellcoho.com/commonhouse/Calendar6/index.php' );
            // header( 'Location: http://localhost/BellcohoReserve/Calendar6/index.php' );
        }
        catch(Google_Service_Exception $gse)
        {
          echo $gse->getMessage();
        }
        catch(Exception $ex)
        {
          echo $ex->getMessage();
        }        
    }
?>
    
<div class="center">
  <table class="full">
    <tr> 
      <td> 
          <table class="heading">
          <tr> 
            <td class="graybox"> 
                <h1 class="pagetitle">Post a Meeting/Event to the Cohousing Calendar</h1>
            </td>
          </tr>
        </table>
        <table class="top-form">
          <tr class='center'> 
              <td class="blackcenter">
                  <form id="googlecalendarform">
                      <table class="reportform" border=2>
                        <tr>
                          <td class="blackleft">Title:</td>
                          <td><input type="text" name="title" id="title" value="<?php echo get_title( $dbConnect ); ?>" />
                        </tr>
                        <tr>
                          <td class="blackleft">From Date:</td>
                          <td><input type="date" name="from_date" id="fromDate" value="<?php echo get_from_date(); ?>" </td>
                        </tr>
                        <tr>
                          <td class="blackleft">From Time:</td>
                          <td><input type='time' name='from_time' id='fromTime' value="<?php echo get_from_time(); ?>"/></td>
                        </tr>
                        <tr>
                          <td class="blackleft">To Date:</td>
                          <td><input type='date' name='to_date' id='toDate' value="<?php echo get_to_date(); ?>"/></td>
                        </tr>
                          <tr>
                          <td class="blackleft">To Time:</td>
                          <td><input type='time' name='to_time' id='toTime' value="<?php echo get_to_time(); ?>"/></td>
                        </tr>
                        <tr>
                          <td class="blackleft">Location:</td>
                          <td><input type="text" name="location" value="<?php echo get_location( $dbConnect ); ?>" />
                        </tr>
                        <tr>
                          <td class="blackleft">Description:</td>
                          <td><textarea rows="4" cols="50" name="description" ><?php echo get_description( $dbConnect ); ?>
                              </textarea>
                        </tr>
                        <tr>
                            <td><button id="cancel" >Cancel</button></td>
                            <td><button id="submit" >Submit Calendar Event</button></td>
                        </tr>
                      </table>
                  </form>
                  <br/>
              </td>
        </tr>
  </table>
</div>
</body>

</html>
