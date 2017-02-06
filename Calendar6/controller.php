<?php
/*
   Copyright 2009 Bernard Peh

   This file is part of PHP Quick Calendar.

   PHP Quick Calendar is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   PHP Quick Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP Quick Calendar.  If not, see <http://www.gnu.org/licenses/>.
*/

?>

<?php
define('QCALENDAR_SYS_PATH', __DIR__ );
require_once('../sys/config/config.php');
require_once( 'QCalendarBase.php' );
?>
<script type='text/javascript'>
/* <![CDATA[ */
qcalendarsyspath = '<?= '.' ?>'+'/';
/* REPLACE qcalendarsyspath = '<?= 'QCALENDAR_WEB_PATH' ?>'+'/'; */
/* ]]> */
</script>

<?php
/*
 * set calendar theme
 *
 * A factory that generates the right object based on user input
 *
 */
function initQCalendar($theme,  $dbConnect, $divCalendar='qCalendar', $divCalendarCss='', 
        $divLongDesc='qCalendarLongDesc', $divLongDescCss='',
        $d=0, $m=0, $y=0, $c=0, $ajax=0) {
	require_once( QCALENDAR_SYS_PATH . "/themes/$theme/model/Calendar.php");
 	if (!$ajax) {
            // REPLACE $stmt = '<script type=\'text/javascript\' src=\''.'QCALENDAR_WEB_PATH'.'/js/qCalendar.js\'></script>';
            $stmt = '<script type=\'text/javascript\' src=\'js/qCalendar.js\'></script>';
		echo $stmt;
		// insert calendar css
		echo "<link href=\"themes/$theme/view/calendar.css\" rel=\"stylesheet\" type=\"text/css\" />";
		// clear all styles first to prevent css inheritance from other parts of the page
		echo "<div style=\"margin: 0;padding: 0;border: 0;outline: 0;font-size: 100%;vertical-align: baseline;background: transparent; text-align:center;\">";
		echo "<div id='$divCalendar' style='$divCalendarCss'>";
	}
	$classname = 'QCalendar'.ucfirst($theme);
	$qcal = new $classname($theme, $dbConnect, $divCalendar, $divLongDesc);
	// if month or year is set
	if ($m || $y || $d || $c) {
		$qcal->setMonth($m);
		$qcal->setYear($y);
		$qcal->setDay($d);
		$qcal->setCategoryId($c);
		$qcal->init();
	}
	// render calendar
	$qcal->render();

	if (!$ajax) {
		echo "</div></div>";
		// insert long desc css
		echo "<link href=\"themes/$theme/view/longdesc.css\" rel=\"stylesheet\" type=\"text/css\" />";
		// render long desc
		echo "<div id='$divLongDesc' style='$divLongDescCss'></div>";
		// clear all styles
		echo "<div style='clear:both'></div>";
	}
}

// if user clicks on month or year navigation, re-render calendar
if (isset($_GET['divCalendar']) && isset($_GET['m'])) {
	initQCalendar($_GET['theme'], $dbConnect, $_GET['divCalendar'], '', 
                $_GET['divLongDesc'], '', $_GET['d'], $_GET['m'],$_GET['y'], $_GET['c'], 1);
	exit();
}
?>


