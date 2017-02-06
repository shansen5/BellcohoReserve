<?php
/*
   Copyright 2009 Bernard Peh

   This file is part of PHP Quick Calendar.

   NOTICE OF LICENSE

   This source file is subject to the Open Software License (OSL 3.0)
   that is bundled with this package in the file LICENSE.txt.
   It is also available through the world-wide-web at this URL:
   http://opensource.org/licenses/osl-3.0.php
*/


// CONFIGURE WEB LOCATION FROM ROOT
define('QCALENDAR_WEB_PATH','/commonhouse/Calendar5');

// CONFIGURE DB ACCESS
// $dbhost = 'cohocalendar.db.10150044.hostedresource.com';
// $dbuser = 'cohocalendar';
// $dbpass = 'Bcca20!3';
// $database = 'cohocalendar';
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

// END OF CONFIGURATION. NOTHING NEEDS TO BE DONE BEYOND THIS POINT.

// start connecting to db
$dbConnect = new PDO("mysql:host=$dbhost;dbname=$database;charset=utf8mb4", $dbuser, $dbpass);
$dbConnect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// $dbConnect = mysql_connect($dbhost, $dbuser, $dbpass);
if (!$dbConnect) {
   die('Could not connect: ' . mysql_error());
}
    // $db_selected = mysql_select_db($database, $dbConnect);
    // if (!$db_selected) {
    //    die ('db selection error : ' . mysql_error());
// }
?>
