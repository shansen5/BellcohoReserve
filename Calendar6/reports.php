<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>

    <link rel="stylesheet" type="text/css" media="all" href="css/bccastyle.css"></link>
    <link rel="stylesheet" type="text/css" media="all" href="themes/datepick/jsDatePick_ltr.min.css"></link>
    <link rel="stylesheet" type="text/css" media="all" href="css/ajax.css"></link>

    <!--
    <script> 
        // wait for the DOM to be loaded 
        $(document).ready(function() { 
            // bind 'myForm' and provide a simple callback function 
            $('#unitreserverpt').ajaxForm(function() { 
                alert("Thank you for your comment!"); 
            }); 
        }); 
    </script> -->

<title>Bellingham Cohousing Room Reservation Reports</title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></meta>

</head>



<body>
<div class="center">
  <table class="full">
    <tr> 
      <td> 
          <table class="heading">
          <tr> 
            <td class="graybox"> 
                <h1 class="pagetitle">Room Reservations Reports</h1>
            </td>
          </tr>
        </table>
        <table class="heading">
          <tr>
            <td class="top-menu"><a class='button' href="index.php">Reservations</a></td>
          </tr>
        </table>
          <h2 class="pagehead">Room Reservations Reports</h2>
        <table class="heading">
          <tr class='center'> 
              <td class="blackcenter">
                  <p>
                    <?php 
                        require_once('controller.php');
                    ?>
                  </p>
                  <br/>
                  <h3>Guest Room Reservations By Unit</h3>
                  <form id="unitreserverpt">
                      <table class="reportform" border=2>
                        <tr>
                          <td class="blackleft">Unit:</td>
                          <td><select id="unit_id" size="1">
                                <option value="0">All</option>
                         
      <?php 
        $id0 = 0;
        $sql0 = "SELECT id as id0 FROM units ORDER BY id";
        try {
            $rs0 = $dbConnect->query( $sql0 );
            $results = $rs0->fetchAll(PDO::FETCH_ASSOC);
            foreach($results as $rw0) {
                $id0 = $rw0['id0'];
                echo '<option value="' .$id0. '">' . $id0 . '</option>';
            }
        } catch(PDOException $ex) {
            echo "Database error" . $ex->getMessage(); //user friendly message
        }
      ?>
                                  </select>
                          </td>
                        </tr>
                        <tr>
                          <td class="blackleft">From Date:</td>
                          <td><input type='text' name='from_date' id='fromDate' size='12' id='fromDate'/></td>
                        </tr>
                        <tr>
                          <td class="blackleft">To Date:</td>
                          <td><input type='text' name='from_date' id='toDate' size='12' id='toDate'/></td>
                        </tr>
                        <tr>
                          <td><input class='button' type="submit" value="Generate Report"></td>
                        </tr>
                      </table>
                  </form>
                  <br/>
              </td>
        </tr>
  </table>

    <script type="text/javascript" src="js/jsDatePick.min.1.3.js"></script>

    <script type="text/javascript">
        window.onload = function(){
                new JsDatePick({
                        useMode:2,
                        target:"fromDate",
                        dateFormat:"%M-%d-%Y",
                        imgPath:"/commonhouse/Calendar5/img/"
                });
                new JsDatePick({
                        useMode:2,
                        target:"toDate",
                        dateFormat:"%M-%d-%Y",
                        imgPath:"/commonhouse/Calendar6/img/"
                });
        };
    </script>
    <script type="text/javascript"  src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script> 
    <script type="text/javascript" src="http://malsup.github.com/jquery.form.js"></script> 
    <script type="text/javascript" src="js/reports.js"></script>
</body>

</html>
