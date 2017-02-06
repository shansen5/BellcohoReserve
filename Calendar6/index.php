<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
      <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>

    <link rel="stylesheet" type="text/css" media="all" href="css/bccastyle.css"></link>
    <link rel="stylesheet" type="text/css" media="all" href="themes/datepick/jsDatePick_ltr.min.css"></link>
    <script type="text/javascript" src="js/jsDatePick.min.1.3.js"></script>

<?php include( "reserve.php" ); ?>

<title>Bellingham Cohousing Room Reservation Calendar</title>

<meta http-equiv="Content-Type" 
      content="text/html; charset=iso-8859-1">
</meta>
<meta name="robots" content="noindex" />

</head>

<body>
<?php
    // require_once( 'access.php' );
    require_once('controller.php');

    $month = date('m');
    $year = date('Y');
    $cat_id = 'All';
    if (isset($_POST) && $_POST != NULL) {
	extract($_POST);
    }
?>
    
<div class="center">
  <table class="full">
    <tr> 
      <td> 
          <table class="heading">
          <tr> 
            <td class="graybox"> 
                <h1 class="pagetitle">Guest and Meeting Room Reservations</h1>
            </td>
          </tr>
        </table>
        <table class="heading">
          <tr>
            <td class="top-menu">
              <a class='button' href="http://bellcoho.com/members/member-home/">Members Home Page</a></td>
          </tr>
        </table>
        <table class="heading">
          <tr><td colspan="3"></td></tr>
          <tr>
              <td />
              <td colspan="2">
                  <form id='categoryform' action='' method='post'>
                      <fieldset>
                          <table class="top-form">
                              <tr class="blackcenter">
		                  <td>Category:</td> 
		                  <td  class="blackcenter">
		                      <select name='cat_id'>
  		                          <?php
                                              $catArray = array('All'=>'All', 'Guest'=>'Guest Rooms', 'Mtg'=>'Meeting/Event');
		
		                               foreach($catArray as $k=>$v) {
			                           echo "<option value='$k'";
			                           if ($cat_id == $k) {
				                       echo ' selected';
			                           }
			                           echo ">$v</option>";
		                               }

		                          ?>
		                      </select>
		                  </td>
		                  <td>&nbsp;<input class='button' type="submit" value="Submit" /></td>
	                      </tr>
                          </table>
                      </fieldset>
                  </form>
              </td>
          </tr>
          
          <tr> 
              <td class="blackcenter">
                  <div class='reservationforms'>
                  <p id="responsemessage">
                    <?php 
                
                        $response = CH_reserve( $dbConnect );
                        echo $response; 
                    ?>
                  </p>
                  
                      <a class='button' href="reports.php">View Reports</a>
                  
                  <p>Guest Room Reservation</p>
                  <form class='reservations' action="" method="post">
                      <table border=2>
                        <tr>
                          <td class="blackleft">Guest Room:</td>
                          <td class="blackleft">
                         
      <?php 
        $id0 = 0;
        $name0 = '';
        $sql0 = "SELECT id as id0, name as name0 FROM common_rooms WHERE type = 'guest'";
        $sql0 .= " OR type = 'guest-meeting'";
        try {
            $rs0 = $dbConnect->query( $sql0 );
            $results = $rs0->fetchAll(PDO::FETCH_ASSOC);
            foreach($results as $rw0) {
                echo "<label><input class=\"checkbox\" name=\"guest_rooms[]\" type='checkbox' value='"
                        . $rw0['id0'] . "'/>" . $rw0['name0'] . "</label><br/>";
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
      ?>
                          </td>
                        </tr>
                        <tr>
                          <td class="blackleft">Name:</td>
                          <td><select name='person_id' size='1'>
                                <option value="0">None</option>
       <?php
        # Populate with all current (>16) adult residents and their current names (end_date is null)
        $first_name = '';
        $last_name = '';
        $person_id = 0;
        $sql2 = 'SELECT c.person_id as person_id, c.first_name as first_name, c.last_name as last_name ';
        $sql2 .= 'FROM  current_members c ';
        $sql2 .= 'WHERE c.type <> "child" ORDER BY c.first_name';
        try {
            $rs2 = $dbConnect->query( $sql2 );
            $results = $rs2->fetchAll(PDO::FETCH_ASSOC);
            foreach($results as $rw2) {
                $person_id = $rw2['person_id'];
                $first_name = $rw2['first_name'];
                $last_name = $rw2['last_name'];
                echo '<option value="' .$person_id. '">' . $first_name . ' ' . $last_name . '</option>';
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
       ?>
                              </select>
                          </td>
                        </tr>
                        <tr>
                          <td class="blackleft">Password:</td>
                          <td><input type='password' name='password' size='15'></td>
                        </tr>
                        <tr>
                          <td class="blackleft">From Date:</td>
                          <td><input type='text' name='from_date' size='12' id='fromDate'/></td>
                        </tr>
                        <tr>
                          <td class="blackleft">To Date:</td>
                          <td><input type='text' name='to_date' size='12' id='toDate'/></td>
                        </tr>
                       <tr>
                          <td class="blackleft" colspan='2'><input type='checkbox' name='cancel'>Cancel my previous reservation</td>
                        </tr>
                       <tr>
                          <td class="blackleft" colspan='2'><input type='checkbox' name='backup'>Add me as backup</td>
                        </tr>
                        <tr>
                          <td><input class='button' type='reset' value='Clear Form'></td>
                          <td><input class='button' type='submit' value="Submit"></td>
                        </tr>
                      </table>
                  </form>
                  <br/>
                  <p>Meeting Room Reservations<br>and Community Events</p>
                  <form class='reservations' action="" method="post">
                      <table border=2>
                        <tr>
                          <td class="blackleft">Meeting Room:</td>
                          <td class="blackleft">
      <?php 
        $sql1 = "SELECT id as id1, name as name1 FROM common_rooms WHERE type = 'meeting'";
        $sql1 .= " OR type = 'guest-meeting' OR type = 'great-room'";
        try {
            $rs1 = $dbConnect->query( $sql1 );
            $results = $rs1->fetchAll(PDO::FETCH_ASSOC);
            foreach($results as $rw1) {
                echo "<label><input class=\"checkbox\" name=\"mtg_rooms[]\" type='checkbox' value='"
                        . $rw1['id1'] . "'/>" . $rw1['name1'] . "</label><br/>";
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
      ?>
                          
                        
                          
                        </tr>
                        <tr>
                          <td class="blackleft">Name:</td>
                          <td><select name='person_id' size='1'>
                                <option value="0">None</option>
       <?php
        # Populate with all current (>16) adult residents and their current names (end_date is null)
        $first_name = '';
        $last_name = '';
        $person_id = 0;
        $sql2 = 'SELECT c.person_id as person_id, c.first_name as first_name, c.last_name as last_name ';
        $sql2 .= 'FROM  current_members c ';
        $sql2 .= 'WHERE c.type <> "child" ORDER BY c.first_name';
        try {
            $rs2 = $dbConnect->query( $sql2 );
            $results = $rs2->fetchAll(PDO::FETCH_ASSOC);
            foreach($results as $rw2) {
                echo '<option value="' .$rw2['person_id']. '">' . $rw2['first_name'] . ' ' . $rw2['last_name'] . '</option>';
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
       ?>
                              </select>
                          </td>
                        </tr>
                        <tr>
                          <td class="blackleft">Committee:</td>
                          <td><select name='committee_id' size='1'>
                                <option value="0">None</option>
       <?php
        # Populate with all committee names, with committee id as value
        $committee_name = '';
        $committee_id = 0;
        $sql3 = 'SELECT id as committee_id, name as committee_name ';
        $sql3 .= 'FROM committees';
        try {
            $rs3 = $dbConnect->query( $sql3 );
            $results = $rs3->fetchAll(PDO::FETCH_ASSOC);
            foreach($results as $rw3) {
                $committee_id = $rw3['committee_id'];
                $committee_name = $rw3['committee_name'];
                echo '<option value="' .$committee_id. '">' . $committee_name . '</option>';
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
                 
       ?>
                              </select>
                          </td>
                        </tr>
                        <tr>
                          <td class="blackleft">Password:</td>
                          <td><input type='password' name='password' size='15'></td>
                        </tr>
                        <tr>
                          <td class="blackleft">Date:</td>
                          <td><input type='text' name='from_date' size='12' id='mtgDate'/></td>
                        </tr>
                        <tr>
                          <td class="blackleft">To Date:</td>
                          <td><input type='text' name='to_date' size='12' id='mtgToDate'/></td>
                        </tr>
                        <tr>
                          <td class="blackleft">From Time:</td>
                          <td><select name='from_time' size='1'>
                                <option value="0">None</option>
       <?php
        echo '<option value="30">12:30 a.m.</option>';
        for ( $i = 1; $i <= 24; $i++ ) {
            if ( $i < 12 ) {
                echo '<option value="' .$i. '00">' . $i . ':00 a.m.</option>';
                echo '<option value="' .$i. '30">' . $i . ':30 a.m.</option>';
            } elseif ($i == 12) {
                echo '<option value="' .$i. '00">noon</option>';
                echo '<option value="' .$i. '30">' . $i . ':30 p.m.</option>';
            } elseif ($i == 24) {
                echo '<option value="' .$i. '00">midnight</option>';
            } else {
                $hr = $i - 12;
                echo '<option value="' .$i. '00">' . $hr . ':00 p.m.</option>';                
                echo '<option value="' .$i. '30">' . $hr . ':30 p.m.</option>';                
            }
        }
       ?>
                              </select>
                          </td>
                        </tr>
                        <tr>
                          <td class="blackleft">To Time:</td>
                          <td><select name='to_time' size='1'>
                                <option value="0">None</option>
       <?php
        echo '<option value="30">12:30 a.m.</option>';
        for ( $i = 1; $i <= 24; $i++ ) {
            if ( $i < 12 ) {
                echo '<option value="' .$i. '00">' . $i . ':00 a.m.</option>';
                echo '<option value="' .$i. '30">' . $i . ':30 a.m.</option>';
            } elseif ($i == 12) {
                echo '<option value="' .$i. '00">noon</option>';
                echo '<option value="' .$i. '30">' . $i . ':30 p.m.</option>';
            } elseif ($i == 24) {
                echo '<option value="' .$i. '00">midnight</option>';
            } else {
                $hr = $i - 12;
                echo '<option value="' .$i. '00">' . $hr . ':00 p.m.</option>';                
                echo '<option value="' .$i. '30">' . $hr . ':30 p.m.</option>';                
            }
        }
       ?>
                              </select>
                          </td>
                        </tr>
                        <tr>
                          <td class="blackleft">Purpose:</td>
                          <td><select name='purpose_id' size='1'>
                                <option value="0">None</option>
       <?php
        $purpose = '';
        $purpose_id = 0;
        $sql3 = 'SELECT id as purpose_id, text as purpose ';
        $sql3 .= 'FROM purposes';
        try {
            $rs3 = $dbConnect->query( $sql3 );
            $results = $rs3->fetchAll(PDO::FETCH_ASSOC);
            foreach($results as $rw3) {
                $purpose_id = $rw3['purpose_id'];
                $purpose = $rw3['purpose'];
                echo '<option value="' .$purpose_id. '">' . $purpose . '</option>';
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
                 
       ?>
                              </select>
                          </td>
                        </tr>
                        <tr>
                          <td class="blackleft">Description:</td>
                          <td><textarea rows="2" cols="20" name="description" id="desc"> </textarea>
                        </tr>
                          <tr>
                          <td class="blackleft">Repeats:</td>
                          <td>
                
                    <input type="checkbox" name="repeat_wom[]" value="1">1st</input>
                    <input type="checkbox" name="repeat_wom[]" value="2">2nd</input>
                    <input type="checkbox" name="repeat_wom[]" value="3">3rd</input>
                    <br/>
                    <input type="checkbox" name="repeat_wom[]" value="4">4th</input>
                    <input type="checkbox" name="repeat_wom[]" value="5">5th</input>
                    <hr>
                    <input type="checkbox" name="repeat_dow[]" value="Mon">M</input>
                    <input type="checkbox" name="repeat_dow[]" value="Tue">Tu</input>
                    <input type="checkbox" name="repeat_dow[]" value="Wed">W</input>
                    <input type="checkbox" name="repeat_dow[]" value="Thu">Th</input>
                    <br/>
                    <input type="checkbox" name="repeat_dow[]" value="Fri">F</input>
                    <input type="checkbox" name="repeat_dow[]" value="Sat">Sa</input>
                    <input type="checkbox" name="repeat_dow[]" value="Sun">Su</input>
                          </td></tr>
                          <tr>
                    <td class="blackleft">Until</td>
                    <td><input type='text' name='until_date' size='12' id='untilDate'/></td>
                </tr>
                          </td>
                          </tr>
                       <tr>
                          <td class="blackleft" colspan='2'><input type='checkbox' name='cancel'>Cancel my previous reservation</td>
                        </tr>
                       <tr>
                          <td class="blackleft" colspan='2'><input type='checkbox' name='cancel_series'>Cancel entire series</td>
                        </tr>
                        <tr>
                          <td><input class='button' type='reset' value='Clear Form'></td>
                          <td><input class='button' type='submit' value="Submit"></td>
                        </tr>
                      </table>
                  </form>
                  </div>
              </td>  
              <td>&nbsp;&nbsp;</td>
              <td>
<?php
                 $cssCalendar= 'float:left;';
                 $cssLongDesc='float:left;margin-left:50px;width:400px;';
                 $cssLongDesc.='overflow:auto;z-index:10;position:absolute;border:1px solid #0066FF; background-color:#FFFFFF; visibility:hidden;';

                 // configure calendar theme
                 initQCalendar('twocolumn', $dbConnect,'qCalendarTwoColumn', 
                         $cssCalendar,'myContentTwoColumn', $cssLongDesc,
                         0,$month,$year,$cat_id,0 );
?>
              </td>

        </tr>
  </table>

    <script type="text/javascript" src="js/jsDatePick.min.1.3.js"></script>
    <script type=""text/javascript"
          src="http://www.google.com/jsapi"></script>
    <script type=""text/javascript">
          google.load("jquery", "1.4.2");
    </script>
    <script type="text/javascript" src="js/jsDatePick.min.1.3.js"></script>
    <script type="text/javascript">
        window.onload = function(){
                new JsDatePick({
                        useMode:2,
                        target:"fromDate",
                        dateFormat:"%M-%d-%Y",
                        imgPath:"/commonhouse/Calendar6/img/"
                });
                new JsDatePick({
                        useMode:2,
                        target:"toDate",
                        dateFormat:"%M-%d-%Y",
                        imgPath:"/commonhouse/Calendar6/img/"
                });
                new JsDatePick({
                        useMode:2,
                        target:"mtgDate",
                        dateFormat:"%M-%d-%Y",
                        imgPath:"/commonhouse/Calendar6/img/"
                });
                new JsDatePick({
                        useMode:2,
                        target:"mtgToDate",
                        dateFormat:"%M-%d-%Y",
                        imgPath:"/commonhouse/Calendar6/img/"
                });
                new JsDatePick({
                        useMode:2,
                        target:"untilDate",
                        dateFormat:"%M-%d-%Y",
                        imgPath:"/commonhouse/Calendar6/img/"
                });
        };
    </script>
  </body>

</html>
