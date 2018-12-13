<?php
//Composer autoloader, manages the inclusion of Google API library
require_once('../sys/config/config.php');
include('../sys/vendor/autoload.php');

try
{
    //Service account e-mail (the calendar should be shared
    //to this address in order to use it)
    $clientEmail = EVENT_CALENDAR_CLIENT_EMAIL; 

    //Let's specify the 'scopes' used by Google Client API,
    //in this case the ones binded to the calendar
    $scopes = array("https://www.googleapis.com/auth/calendar",
    "https://www.googleapis.com/auth/calendar.readonly");

    //Let's create the instance of the Google Client object,
    //and associate an application name (note: I have still no
    //idea where that application name is used!)
    $client = new Google_Client();
    $client->setApplicationName('BellCoho Reservations Event');

    // New - https://github.com/google/google-api-php-client/blob/master/UPGRADING.md#google_auth_assertioncredentials-has-been-removed
    $key_file = EVENT_CALENDAR_PRIVATE_KEY;
    $client->setAuthConfig( $key_file );
    $client->setSubject( $clientEmail );
    $client->setScopes( $scopes );

    //Let's create a Google_Service_Calendar instance. We will
    //use it to dialog with the shared calendar
    $service = new Google_Service_Calendar($client);

    //Get the list of the calendars shared with the service
    //account and print them
    // $list = $service->calendarList->listCalendarList();
    // echo '<pre>'.var_export($list,true).'</pre>';
  
    //Let’s assume that a string has been sent via POST and that it
    //contains date and hour of the event (e.g. 03/05/2015 20:00)
    //First of all, let’s convert the date and time stored in
    //$_POST[‘dateAndHour’] in a RFC 3339 compliant string, then set
    //the end of the event 30 minutes after the beginning

    // $startDate = strftime(‘%Y-%m-%dT%H:%M:%S’, strtotime($_POST[‘dateAndHour’]));
    // $endDate = strftime(‘%Y-%m-%dT%H:%M:%S’, strtotime($_POST[‘dateAndHour’] . ‘ + 30 minutes’));

    $dateString = '05/01/2018 20:00';
    $startDate = strftime('%Y-%m-%dT%H:%M:%S', strtotime($dateString));
    $endDate = strftime('%Y-%m-%dT%H:%M:%S', strtotime($dateString . ' + 30 minutes'));

    $event = new Google_Service_Calendar_Event();

    //Set the summary (= the text shown in the event)
    $event->setSummary('Test event');

    //Set the place of the event
    $event->setLocation('office');

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

    //Let’s add the event to the calendar
    $createdEvent = $service->events->insert(EVENT_CALENDAR_ACCOUNT,$event);

    //Show the ID of the created event
    echo '< div >' . $createdEvent->getId() . '< /div >';
}
catch(Google_Service_Exception $gse)
{
  echo $gse->getMessage();
}
catch(Exception $ex)
{
  echo $ex->getMessage();
}

?>