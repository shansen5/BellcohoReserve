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


/*
 * The standard QCalendar Class
 * 
 * Quick Calendar parent class. The user can then display the calendar using the render function.
 *
 */
class QCalendarBase {
	
	// calendar html to render
	var $html;
	
	// no. of days in current month
	var $daysInMonth;
	
	// no. of weeks in current month
	var $weeksInMonth;
	
	// days in array
	var $cell;
	
	// first day of month
	var $firstDay;
	
	// this month
	var $month;
	
	// css name for calendar
	var $css;
	
	// this year
	var $year;
	
	// this day
	var $day;
	
	// this category
	var $cat_id;
	
	// today in array format
	var $today;
	
	// links in array. links will appear in the cell.
	var $links;
	
	// header description
	var $header;
	
	// next month
	var $nextMonth;
	
	// previous month
	var $prevMonth;
	
	// next year. why i named it lYear i dont know.
	var $lYear;
	
	// previous year
	var $pYear;
	
	// calendar theme
	var $theme;
	
	// calendar div
	var $divCalendar;
	
	// calendar long description div
	var $divLongDesc;
	
	// view container
	var $view;
	
        // Database connection
        var $dbConnect;
        
	/**
	 * The constructor 
	 *
	 * The constructor initialises the calendar.
	 *
	 */
	function QCalendarBase($theme, $dbConnect, $divCalendar, $divLongDesc) {
		// set css
		$this->setCss($theme);
		// set theme
		$this->theme = $theme;
		$this->divCalendar = $divCalendar;
		$this->divLongDesc = $divLongDesc;
                // Database connection
                $this->dbConnect = $dbConnect;
		// This year
		$this->year  = date('Y');
		// This month
		$this->month = date('n');
		// This Day
		$this->day = date('j');
		// Selected Category ID
		$this->cat_id = 'All';
		$this->today = array('day'=>$this->day, 'month'=>$this->month, 'year'=>$this->year);
		$this->init();
	}
	
	// $firstColumn: 0 is a sunday
	function init() {
		$this->cell = array();
		$this->links = array();
		$this->view = array();
		$this->daysInMonth = date("t",mktime(0,0,0,intval($this->month),1,intval($this->year)));
		// get first day of the month
		$this->firstDay = date("w", mktime(0,0,0,intval($this->month),1,intval($this->year)));
		$tempDays = $this->firstDay + $this->daysInMonth;
		$this->weeksInMonth = ceil($tempDays/7);
		$this->createLinks();
		$this->fillArray();
		$this->buildNavigationVar();	
	}
	/**
	 * Check if user uses ajax
	 *
	 */
	function createLinks() {
                $purpose_id = 0;
                $comment_id = 0;
		$sql = "SELECT q.id, q.use_type, q.day, q.month, q.year, q.reserve_day, q.reserve_month, ";
                $sql .= "q.reserve_year, q.cancel_day, q.cancel_month, q.cancel_year, q.person_id, q.comment_id, ";
                $sql .= "q.start_time, q.end_time, q.purpose_id, q.committee_id, q.backup, ";
                $sql .= "r.name as room_name FROM ".QCALENDAR_TABLE." as q, ";
                $sql .= QCALENDAR_ROOM_TABLE." as r ";
                $sql .= "WHERE q.cancel_day is null and q.month='$this->month'";
                $sql .= " AND q.year='$this->year' AND r.id=q.room_id ";
                $sql .= "ORDER BY q.committee_id desc, q.backup, q.id";
                // Order by committee_id desc to get meeting rooms at the top of the list.

                try {
                    $rs = $this->dbConnect->query( $sql );
                    $results = $rs->fetchAll(PDO::FETCH_ASSOC);
                    foreach($results as $rw) {
                        $room_name = $rw['room_name'];
                        $reserve_year = $rw['reserve_year'];
                        $reserve_month = $rw['reserve_month'];
                        $reserve_day = $rw['reserve_day'];
                        $committee_id = $rw['committee_id'];

                        $reserve_date = $reserve_year . '-' . $reserve_month . '-' . $reserve_day;

                        $unit_id = -1;
                        try {
                            $sql1 = "SELECT unit_id FROM ".QCALENDAR_PERSONUNITS_TABLE;
                            $sql1 .= " WHERE person_id = ". $rw['person_id'] . " AND start_date < '" . $reserve_date;
                            $sql1 .= "' AND ( end_date is null OR end_date > '" . $reserve_date . "')";
                            $rs1 = $this->dbConnect->query( $sql1 );                          
                            $rw1 = $rs1->fetch( PDO::FETCH_ASSOC );
                            if ( $rw1 ) {
                                $unit_id = $rw1['unit_id'];                                
                            }
                        } catch(PDOException $ex) {
                            echo "Database error" . $ex->getMessage(); //user friendly message
                        }

                        $first_name = '';
                        $last_name = '';
                        try {
                            $sql1 = "SELECT first_name, last_name FROM ".QCALENDAR_PERSONNAMES_TABLE;
                            $sql1 .= " WHERE person_id = ". $rw['person_id'] . " AND start_date < '" . $reserve_date;
                            $sql1 .= "' AND ( end_date is null OR end_date > '" . $reserve_date . "')";

                            $rs1 = $this->dbConnect->query( $sql1 );                          
                            $rw1 = $rs1->fetch( PDO::FETCH_ASSOC );
                            if ( $rw1 ) {
                                $first_name = $rw1['first_name'];  
                                $last_name = $rw1['last_name'];
                            }
                        } catch(PDOException $ex) {
                            echo "Database error" . $ex->getMessage(); //user friendly message
                        }

                        $committee_name = '';
                        if ( $committee_id > 0 ) {
                            try {
                                $sql1 = "SELECT name as committee_name FROM ".QCALENDAR_COMMITTEES_TABLE;
                                $sql1 .= " WHERE id = ". $committee_id;

                                $rs1 = $this->dbConnect->query( $sql1 );                          
                                $rw1 = $rs1->fetch( PDO::FETCH_ASSOC );
                                if ( $rw1 ) {
                                    $committee_name = $rw1['committee_name'];  
                                }
                            } catch(PDOException $ex) {
                                echo "Database error" . $ex->getMessage(); //user friendly message
                            }
                        }

                        $purpose = '';
                        if ( $purpose_id > 0 ) {
                            try {
                                $sql1 = "SELECT text as purpose FROM ".QCALENDAR_PURPOSES_TABLE;
                                $sql1 .= " WHERE id = ". $purpose_id;

                                $rs1 = $this->dbConnect->query( $sql1 );                          
                                $rw1 = $rs1->fetch( PDO::FETCH_ASSOC );
                                if ( $rw1 ) {
                                    $purpose = $rw1['purpose'];  
                                }
                            } catch(PDOException $ex) {
                                echo "Database error" . $ex->getMessage(); //user friendly message
                            }
                        }

                        $comment = '';
                        if ( $purpose_id > 0 ) {
                            try {
                                $sql1 = "SELECT text as comment FROM ".QCALENDAR_COMMENTS_TABLE;
                                $sql1 .= " WHERE id = " . $comment_id;

                                $rs1 = $this->dbConnect->query( $sql1 );                          
                                $rw1 = $rs1->fetch( PDO::FETCH_ASSOC );
                                if ( $rw1 ) {
                                    $comment = $rw1['comment'];  
                                }
                            } catch(PDOException $ex) {
                                echo "Database error" . $ex->getMessage(); //user friendly message
                            }
                        }
                        $this->links[] = array('id'=>$rw['id'], 'use_type'=>$rw['use_type'], 
                            'committee_name'=>$committee_name, 'day'=>$rw['day'], 'month'=>$rw['month'], 
                            'year'=>$rw['year'], 'start_time'=>$rw['start_time'], 'end_time'=>$rw['end_time'],
                            'room'=>$room_name, 'unit' => $unit_id, 'first_name' => $first_name, 
                            'last_name' => $last_name, 'reserve_day'=>$rw['reserve_day'], 
                            'reserve_month'=>$rw['reserve_month'], 'reserve_year'=>$rw['reserve_year'],
                            'purpose'=>$purpose, 'description'=>$comment, 'backup'=>$rw['backup']);
                    }
                } catch(PDOException $ex) {
                    echo "Database error" . $ex->getMessage(); //user friendly message
                }

	}	
	
	/**
	 * register variables to View
	 *
	 * @param Array $view
	 */
	 function registerView($view) {
		if (is_array($view)) {
			foreach ($view as $k => $v) {
				$this->view[$k] = $v;
			}
		}
		else {
			exit('$view must be an array');
		}
	 }
	 
	/**
	 * set css name of table
	 *
	 * @param String $css
	 */
	 function setCss($css) {
		$this->css=$css;
	 }
	 
	 /**
	 * set month
	 *
	 * @param int $m
	 */
	 function setMonth($m) {
		$this->month=$m;
	 }
	 
	 /**
	 * set year
	 *
	 * @param int $y
	 */
	 function setYear($y) {
		$this->year=$y;
	 }
	 
	 /**
	 * set day
	 *
	 * @param int $d
	 */
	 function setDay($d) {
		$this->day=$d;
	 }
	 
	 /**
	 * set category id
	 *
	 * @param string $cat_id
	 */
	 function setCategoryId($cat_id) {
		$this->cat_id=$cat_id;
	 }
	 
	/**
	 * The calendar is created using a 2-D array. This function fills the array with the right values. 0 is a sunday.
	 *
	 * @param Int $firstColumn
	 */
	function fillArray($firstColumn=0) {
		// create a 2-d array
		$counter = $firstColumn;
		if ($firstColumn > $this->firstDay) {
			$counter = -(7 - $firstColumn);
			$this->weeksInMonth++;
		}
		
		for($i=0; $i<$this->weeksInMonth; $i++) {
			// if days in month exceeded, break out of loop
			if ($counter - $this->firstDay + 1 > $this->daysInMonth) {
				$this->weeksInMonth--;
			}
				
			for($j=0;$j<7;$j++) {				
				$counter++;
				$this->cell[$i][$j]['value'] = $counter; 
				// offset the days
				$this->cell[$i][$j]['value'] -= $this->firstDay;
				if (($this->cell[$i][$j]['value'] < 1) || ($this->cell[$i][$j]['value'] > $this->daysInMonth)) {	
					$this->cell[$i][$j]['value'] = '';
				}
			}
		}
	}
	
	function buildNavigationVar() {
		$this->header = date('M', mktime(0,0,0,intval($this->month),1,intval($this->year))).' '.$this->year;
		$this->nextMonth = $this->month+1;
		$this->prevMonth = $this->month-1;

		switch($this->month) {
			case 1:
	   			$this->lYear = $this->year;
   				$this->pYear = $this->year-1;
   				$this->nextMonth=2;
   				$this->prevMonth=12;
   				break;
  			case 12:
   				$this->lYear = $this->year+1;
   				$this->pYear = $this->year;
   				$this->nextMonth=1;
   				$this->prevMonth=11;
      			break;
  			default:
      			$this->lYear = $this->year;
	   			$this->pYear = $this->year;
    	  		break;
  		}
	}
	
	/**
	 * default html header
	 */
	function createHeader() {
		$view = array();
		$view['lastYear'] = "displayQCalendar('$this->theme', '$this->divCalendar', '$this->divLongDesc', '$this->day', '$this->month','".($this->year-1)."', '$this->cat_id')";
		$view['lastMonth'] = "displayQCalendar('$this->theme', '$this->divCalendar', '$this->divLongDesc', '$this->day', '$this->prevMonth','$this->pYear', '$this->cat_id')";
		$view['header'] = $this->header;
		$view['nextMonth'] = "displayQCalendar('$this->theme', '$this->divCalendar', '$this->divLongDesc', '$this->day', '$this->nextMonth','$this->lYear', '$this->cat_id')";
		$view['nextYear'] = "displayQCalendar('$this->theme', '$this->divCalendar', '$this->divLongDesc', '$this->day', '$this->month','".($this->year+1)."', '$this->cat_id')";
		// register view variable
		$this->registerView($view);
	}
	
	/**
	 * default html body
	 */
	function createBody() {
		$view = array();
		$view['weeksInMonth'] = $this->weeksInMonth;
		$this->registerView($view);
	}
	
	/**
	 * default html footer
	 */
	function createFooter() {
		// nothing for now
	}
	
	/**
	 * Call this function to render the html
	 *
	 */
	function render() {
		// create HTML
		$this->createHeader();
		$this->createBody();
		$this->createFooter();
		// add standard var
		$this->view['css'] = $this->css;
		$this->view['QCALENDAR_SYS_PATH'] = QCALENDAR_SYS_PATH;
		$this->view['QCALENDAR_WEB_PATH'] = QCALENDAR_WEB_PATH;
		require_once(QCALENDAR_SYS_PATH."/QCalendarView.php");
		new QCalendarView($this->view, $this->theme, 'calendar.phtml');
	}
}
?>
