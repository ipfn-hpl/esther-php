<!doctype html>
<html lang="en">
   <head>
     <meta charset="utf-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <meta name="description" content="A layout example with a side menu that hides on mobile, just like the Pure website.">
     <title>Esther Pulse Reports</title>

     <!-- http://purecss.io/ -->
     <!-- <link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.6.0/pure-min.css"> -->
	<link rel="stylesheet" href="https://unpkg.com/purecss@2.0.3/build/pure-min.css" integrity="sha384-cg6SkqEOCV1NbJoCu11+bm0NvBRc8IYLRGXkmNrqUBfTjmMYwNKPWBTIKyw9mHNJ" crossorigin="anonymous">
    <!-- <link rel="stylesheet" href="pure-release-0.6.0/pure-min.css"> -->
    <link rel="stylesheet" href="layouts/side-menu/styles.css">

    <!--[if lte IE 8]>
        <link rel="stylesheet" href="/combo/1.18.13?/css/layouts/side-menu-old-ie.css">
	<![endif]-->
    <!--[if gt IE 8]>
        <link rel="stylesheet" href="/combo/1.18.13?/css/layouts/side-menu.css">
	<![endif]  -->

	<!--[if lt IE 9]>
	    <script src="http://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7/html5shiv.js"></script>
	    <![endif]-->

    <!--.
    <style>
  body {
    background-color: #d0e4fe;
  }

  h1 {
     color: orange;
    text-align: center;
  }

 p {
  font-family: "Times New Roman";
  font-size: 20px;
  }

 table, th, td {
   border: 1px solid black;
 }

 th {
   text-align: left;
 }

    </style>
    -->


  </head>

   <body>
     <div id="layout">
       <!-- Menu toggle -->
       <a href="#menu" id="menuLink" class="menu-link">
	 <!-- Hamburger icon -->
	 <span></span>
       </a>

       <div id="menu">
	 <div class="pure-menu">
	   <a class="pure-menu-heading" href="../index.php">ESTHER</a>

	   <ul class="pure-menu-list">
	     <li class="pure-menu-item"><a href="#" class="pure-menu-link">Home</a></li>
	     <li>  <a href="new_report.php" class="pure-menu-link">New Report</a> </li>
	     <li class="pure-menu-item"><a href="./esther_manual.html" class="pure-menu-link">Manual</a></li>

	     <li class="pure-menu-item"><a href="#" class="pure-menu-link">Contact</a></li>
	   </ul>
	 </div>
       </div>

       <div id="main">
	 <div class="header">
	   <h1> Esther Lab</h1>
	   <h2>Bombe Pulse Report</h2>

	 </div>

	 <div class="content">
<!--	   <h2 class="content-subhead">How to use this layout</h2> -->

<?php // esther_show_report.php <!--...-->
  require_once 'login.php';
/*local functions*/
function partial_vol($pr, $tempC, $bvol) {
  //      $bvol=3.02; // Bombe Volume in liters
      $tK=273.15 + $tempC;
      $vp= $pr/1.013/$tK*273.15*$bvol;
      //return  number_format((float)$vp, 2, '.', ''); // return 2 decimals
      return $vp;
   }
function pp_ref($pr, $tempC) {
      $tK=273.15 + $tempC;
      $ppr= $pr/$tK*273.15;
      return $ppr;
   }
  function dec2($a) {
      return  number_format((float)$a, 2, '.', '');
  }
/*****/
  $connection = new mysqli($db_hostname, $db_username, $db_password, $db_database);

  if ($connection->connect_error) die($connection->connect_error);

  if (isset($_POST['shot_id']) )
  {
    $shot_id=$_POST['shot_id'];
  }
  else{
   $query  = "SELECT shot_number FROM esther_reports ORDER BY shot_number DESC LIMIT 1";
   $result = $connection->query($query);
   $row = $result->fetch_array(MYSQLI_NUM);
   $shot_id=$row[0];
  }

echo "Fetching report: $shot_id ";

echo <<<_END
  <form class="pure-form" action="show_report.php" method="post">
       Get Pulse Number:<input type="number" name="shot_id" min="0" value="$shot_id">
  <button type="submit" class="pure-button pure-button-primary"> GET REPORT </button>
</form>
_END;

  $query  = "SELECT manager_name, esther_reports.*, ignition_source, ignition_regime  FROM esther_reports NATURAL JOIN esther_managers NATURAL JOIN ignition_source NATURAL JOIN ignition_regime WHERE shot_number=$shot_id";
  $result = $connection->query($query);

  if (!$result) die ("Database access failed: " . $connection->error);
  $row = $result->fetch_array(MYSQLI_NUM);

$timestamp = strtotime($row[3]);
$date = date('d-m-Y', $timestamp);
$stime = date('H:i:s', $timestamp);
$timestamp = strtotime($row[4]);
$etime = date('H:i:s', $timestamp);

$bvol=$row[37]; // liters
//echo "<p>bvol= $bvol</p>";
//$bvol=3.03; // liters
$tempC= $row[5];
$p0=$row[20] + $row[6]/1000.0; //starting abs pressure

$vHe0= partial_vol($p0, $tempC, $bvol); $vHe0d=dec2($vHe0);

$vO2= partial_vol($row[22]-$row[20], $tempC, $bvol );  $vO2d=dec2($vO2);
$vHe1= partial_vol($row[23]-$row[22], $tempC, $bvol); $vHe1d=dec2($vHe1);
$vH2= partial_vol($row[24]-$row[23], $tempC, $bvol); $vH2d=dec2($vH2);
$vHe2= partial_vol($row[25]-$row[24], $tempC, $bvol); $vHe2d=dec2($vHe2);
$vTot= $vHe0 + $vO2 +$vHe1 +$vH2 +$vHe2; $vTotd= dec2($vTot);
$vHeTot= $vHe0 + $vHe1 + $vHe2; $vHeTotd= dec2($vHeTot);
$rtHe = ($vHeTot)/ $vO2; $rtHed =dec2($rtHe);
$rtH2 = ($vH2)/ $vO2; $rtH2d =dec2($rtH2);
$rtOHe = ($vHeTot)/ $vH2*2; $rtOHed =dec2($rtOHe);
$rtO2 = ($vO2)/ $vH2*2; $rtO2d =dec2($rtO2);

$ppO2= pp_ref($row[22]-$row[20], $tempC);  $ppO2d=dec2($ppO2);
$ppHeI= pp_ref($row[23]-$row[22], $tempC); $ppHeId=dec2($ppHeI);
$ppH2= pp_ref($row[24]-$row[23], $tempC);  $ppH2d=dec2($ppH2);
$ppHeII= pp_ref($row[25]-$row[24], $tempC); $ppHeIId=dec2($ppHeII);

//  $ppO2d , $row[31] ; $ppHeId,  $row[33]; $ppH2d, $row[32]; $ppHeIId, $row[34]
echo <<<_END
<h3>Bottle Pressures (Bar)</h2>

<table class="pure-table">
  <thead>
  <tr>
    <td> </td>
    <th>O2 Bottle</th>
    <th>He I Bottle</th>
    <th>H2  Bottle</th>
    <th>He II  Bottle</th>
    <th>N2 Bottle</th>
    <th>Command N2</th>
  </tr>
 </thead>
<tbody>
  <tr>
    <th>Initial</th>
    <td>$row[10]</td>
    <td>$row[12]</td>
    <td>$row[14]</td>
    <td>$row[16]</td>
    <td>$row[8]</td>
    <td>$row[18]</td>
  </tr>
  <tr>
    <th>Final</th>
    <td>$row[11]</td>
    <td>$row[13]</td>
    <td>$row[15]</td>
    <td>$row[17]</td>
    <td>$row[9]</td>
    <td>$row[19]</td>
  </tr>
</tbody>
</table>

<h3>Partial Pressures (Bar)</h2>

<table class="pure-table">
  <thead>
   <tr>
     <th>N2/He Purge</th>
     <th>Oxigen Fill</th>
     <th>Helium I Fill</th>
     <th>Hidrogen  Fill</th>
     <th>Helium II Fill</th>
     <th>Target </th>
   </tr>
  </thead>
  <tbody>
    <tr>
     <td>$row[20]</td>
     <td>$row[22]</td>
     <td>$row[23]</td>
     <td>$row[24]</td>
     <td>$row[25]</td>
     <td>$row[21]</td>
    </tr>
  </tbody>
</table>

<h3>Partial Volumes (normalized liters)</h2>

<table class="pure-table">
  <thead>
   <tr>
     <th></th>
     <th>Initial</th>
     <th>Oxigen</th>
     <th>Helium I</th>
     <th>Hidrogen</th>
     <th>Helium II</th>
     <th>Total Helium</th>
   </tr>
  </thead>
  <tbody>
    <tr>
     <th>Setting</th>
     <td>$bvol</td>
     <td>$row[31]</td>
     <td>$row[33]</td>
     <td>$row[32]</td>
     <td>$row[34]</td>
     <td></td>
    </tr>
    <tr>
     <th>Measured</th>
     <td>$vHe0d</td>
     <td>$vO2d</td>
     <td>$vHe1d</td>
     <td>$vH2d</td>
     <td>$vHe2d</td>
     <td>$vHeTotd</td>
    </tr>
    <tr>
     <th>Mol Rat O2</th>
     <td></td>
     <td>1</td>
     <td></td>
     <td>$rtH2d</td>
     <td></td>
     <td>$rtHed</td>
    </tr>
    <tr>
     <th>Mol Rat H2</th>
     <td></td>
     <td>$rtO2d</td>
     <td></td>
     <td>2.0</td>
     <td></td>
     <td>$rtOHed</td>
    </tr>
</tbody>
</table>

<pre>
    Manager: $row[0]     Pulse Number: $row[1]
    Date: $date      Start Time: $stime Rest Time 00:$row[35]:00 Stop Time: $etime
    ambient_temperature: $row[5] (&#176;C)
    ambient_pressure:    $row[6] (mBar)
    ambient_humidity:    $row[7] (&#37;)
    Wire Voltage: $row[26] (V)     Wire time: $row[27] (ms)
    He/H2/O2 Ratio:     $row[39]:$row[40]:$row[41]
    delta_P_kistler:     $row[28] (Bar)  Range Kistler:  $row[36] (Bar)
    PLC_SW_Version:      $row[29]
    Ignition Source:     $row[45]
    Ignition Regime:     $row[46]

  $ppO2d  $row[31]\t $ppHeId $row[33] \t $ppH2d $row[32] \t $ppHeIId $row[34]
</pre>

<p>Anomalies: </p>
<textarea rows="3" cols="60"> $row[30] </textarea>
<div class="pure-g">
  <div class="pure-u-1-2"><p>
_END;

    //vHe0=$vHe0
    //vO2=$vO2
    //vHe1=$vHe1
    //vH2=$vH2
    //vHe2=$vHe2
    //vTotal=$vTot
//echo number_format((float)$v0, 2, '.', '');
$filename='plots/rp_kistler-'. $shot_id .'.png';
if (file_exists($filename)) {
//	echo "The file $filename exists";
      echo "<img class='pure-img-responsive' src=$filename alt='Red Pitaya plot'>";
}
echo 'RP</p></div>';
echo '  <div class="pure-u-1-2"><p>';

$filename='plots/mcc_kistler-'. $shot_id .'.png';
if (file_exists($filename)) {
//	echo "The file $filename exists";
      echo "<img class='pure-img-responsive' src=$filename alt='MCC plot'>";
}
echo 'MCC</p></div>';

echo '</div>';



/*
    anomalies        $row[30]
    N2_bottle_initial: $row[8]
    O2_bottle_initial: $row[10]
    He1_bottle_initial: $row[12]
    H_bottle_initial: $row[14]
    He2_bottle_initial: $row[16]
    N2_command_bottle_initial:$row[18]
*/


  $result->close();
  $connection->close();

  function get_post($connection, $var)
  {
    return $connection->real_escape_string($_POST[$var]);
  }
?>

         </div>
       </div>
     </div>
<script src="/js/ui.js"></script>
   </body>
 </html>
