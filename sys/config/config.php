<?php

// CONFIGURE WEB LOCATION FROM ROOT
define('QCALENDAR_WEB_PATH','/commonhouse/Calendar6');

// CONFIGURE DB ACCESS
// $dbhost = 'cohocalendar.db.10150044.hostedresource.com';
// $dbuser = 'xxxxxxxxx';
// $dbpass = 'xxxxxxx';
// $database = 'xxxxxxxx';
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = 'Seh0531';
$database = 'bellcoho_reservations';

// Define tables
define('QCALENDAR_TABLE','reservations');
define('QCALENDAR_ROOM_TABLE','common_rooms');
define('QCALENDAR_PEOPLE_TABLE','people');
define('QCALENDAR_PERSONNAMES_TABLE','person_names');
define('QCALENDAR_PERSONUNITS_TABLE','person_units');
define('QCALENDAR_COMMITTEES_TABLE','committees');
define('QCALENDAR_PURPOSES_TABLE','purposes');
define('QCALENDAR_COMMENTS_TABLE','comments');

define('EVENT_CALENDAR_PRIVATE_KEY', '../sys/config/BellcohoReservations-be157d084b30.json');
define('EVENT_CALENDAR_ACCOUNT', 'bellcohoweb@gmail.com');
define('EVENT_CALENDAR_CLIENT_EMAIL', 'reservation-calendar@bellcoho-reservations.iam.gserviceaccount.com');

define('RESERVE_ADMIN_ID', '119');
// END OF CONFIGURATION. NOTHING NEEDS TO BE DONE BEYOND THIS POINT.

// start connecting to db
$dbConnect = new PDO("mysql:host=$dbhost;dbname=$database;charset=utf8mb4", $dbuser, $dbpass);
$dbConnect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if (!$dbConnect) {
   die('Could not connect: ' . mysql_error());
}
?>
