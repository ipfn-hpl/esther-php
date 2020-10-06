 <html>
  <head>
  <title>Esther New Report Form </title>
  </head>
  <body>
  <h1> Esther new Report Form </h1>

<?php // new_report.php
  require_once 'login.php';

  function get_last_value($msq_conn, $ch_id) {
    //    $query  = "SELECT `float_val` FROM `sample` WHERE `channel_id`=$ch_id AND `float_val` IS NOT NULL ORDER BY ABS(TIMESTAMPDIFF(second, `smpl_time`, now())) ASC  LIMIT 1";
    $query  = "SELECT `float_val` FROM `sample` WHERE `channel_id`=$ch_id AND `float_val` IS NOT NULL ORDER BY `smpl_time` DESC LIMIT 1";

    //    echo $query. "<br>";
    $result = $msq_conn->query($query);
    if (!$result) die ("Database access failed: " . $connection->error);
    $row = $result->fetch_array(MYSQLI_NUM);
    $val=$row[0];
    return $val;
  }

  function get_post($connection, $var)
  {
    return $connection->real_escape_string($_POST[$var]);
  }

  $connection = new mysqli($db_hostname, $db_username, $db_password, $db_database);

  if ($connection->connect_error) die($connection->connect_error);

  if (isset($_POST['newrecord']) && isset($_POST['manager_id']))
  {
    $manager_id      = get_post($connection, 'manager_id');
    $ambient_temp    = get_post($connection, 'ambient_temp');
    $ambient_press   = get_post($connection, 'ambient_press');
    $ambient_hum     = get_post($connection, 'ambient_hum');
    $filling_pressure_sp = get_post($connection, 'filling_pressure_sp');
    $He_ratio_sp= get_post($connection, 'He_ratio_sp');
    $H2_ratio_sp= get_post($connection, 'H2_ratio_sp');
    $O2_ratio_sp= get_post($connection, 'O2_ratio_sp');

    $bombe_volume = get_post($connection, 'bombe_volume');

    $PT101=get_last_value($connection, 5);
    $PT201=get_last_value($connection, 9);
    $PT301=get_last_value($connection, 11);
    $PT401=get_last_value($connection, 13);
    $PT501=get_last_value($connection, 15);
    $PT801=get_last_value($connection, 6);

    $query  = "INSERT INTO esther_reports (shot_number, manager_id, start_time, ambient_temperature, ambient_pressure, ambient_humidity, ".
     "N2_bottle_initial, O2_bottle_initial, He1_bottle_initial, H_bottle_initial, He2_bottle_initial, N2_command_bottle_initial, ".
    "bombe_volume, filling_pressure_sp, He_ratio_sp, H2_ratio_sp, O2_ratio_sp, series_id)".
     "VALUES (NULL, $manager_id, now(), $ambient_temp, $ambient_press, $ambient_hum, $PT101, $PT201, $PT301, $PT401, $PT501, $PT801, ".
    "$bombe_volume, $filling_pressure_sp, $He_ratio_sp, $H2_ratio_sp, $O2_ratio_sp, 1)";

    $result = $connection->query($query);

    if (!$result) echo "INSERT  failed: $query<br>" .
      $connection->error . "<br><br>";

    $shot_id=$connection->insert_id;
    $query  = "SELECT start_time FROM esther_reports WHERE `shot_number`=$shot_id";
    $result = $connection->query($query);
    $row = $result->fetch_array(MYSQLI_NUM);
    $times=$row[0];

    echo "Inserting new record Id:". $shot_id. "<br>";
    //echo "The Insert Id was: " . $shot_id; // mysql_insert_id();i
    $sum_ratios =$He_ratio_sp  + $H2_ratio_sp + $O2_ratio_sp;
    $He_partial_sp =$filling_pressure_sp / $sum_ratios * $He_ratio_sp;
    $H2_partial_sp =$filling_pressure_sp / $sum_ratios * $H2_ratio_sp;
    $O2_partial_sp =$filling_pressure_sp / $sum_ratios * $O2_ratio_sp;
    $He1_ratio = 2.0/3; //

    $He_partial_fp = round($He_partial_sp,2);
    $H2_partial_fp = round($H2_partial_sp,2);
    $O2_partial_fp = round($O2_partial_sp,2);

    $He1_vol_sp = $He_partial_sp * $bombe_volume * $He1_ratio - $bombe_volume;
    $He2_vol_sp = $He_partial_sp * $bombe_volume * (1-$He1_ratio);
    $H2_vol_sp = $H2_partial_sp * $bombe_volume;
    $O2_vol_sp = $O2_partial_sp * $bombe_volume;

    $He1_vol_fp= round($He1_vol_sp, 2);
    $He2_vol_fp= round($He2_vol_sp, 2);
    $H2_vol_fp= round($H2_vol_sp, 2);
    $O2_vol_fp= round($O2_vol_sp, 2);

    $O2_end_s3_fp= round($O2_vol_sp/$bombe_volume + $ambient_press/1013 -1.0, 2);
    $He1_end_s4_fp= round($He1_vol_sp/$bombe_volume + $O2_end_s3_fp , 2);
    $H2_end_s6_fp= round($H2_vol_sp/$bombe_volume + $He1_end_s4_fp , 2);
    $He2_end_s7_fp= round($He2_vol_sp/$bombe_volume + $H2_end_s6_fp, 2);

    echo <<<_END
<p> Partial Pressures: </p>
<table border="1">
<tr>
<th>P Partial He (Bar)</th>
<th>P Partial H2 (Bar)</th>
<th>P Partial O2 (Bar)</th>
</tr>
<tr>
<td>$He_partial_fp</td>
<td>$H2_partial_fp</td>
<td>$O2_partial_fp</td>
</tr>
</table>

<p> PT901 Stage Pressure Setpoints: </p>
<table border="1">
<tr>
<th> S3 - O2  (rel. Bar)</th>
<th> S4 - He1  (rel. Bar)</th>
<th> S6 - H2  (rel. Bar)</th>
<th> S7 - He2  (rel. Bar)</th>
</tr>
<tr>
<td>$O2_end_s3_fp</td>
<td>$He1_end_s4_fp</td>
<td>$H2_end_s6_fp</td>
<td>$He2_end_s7_fp</td>
</tr>
</table>

<p> Volume Setpoints: </p>
<table border="1">
<tr>
<th> O2 (nLiter)</th>
<th> He1 (nLiter)</th>
<th> H2 (nLiter)</th>
<th> He2 (nLiter)</th>
</tr>
<tr>
<td>$O2_vol_fp</td>
<td>$He1_vol_fp</td>
<td>$H2_vol_fp</td>
<td>$He2_vol_fp</td>
</tr>
</table>
_END;
    //echo "The Insert Id msqli was: " . $connection->mysql_insert_id();

  }

elseif (isset($_POST['endrecord']) && isset($_POST['shot_id_post']))
  {

    echo "ending record ";
    $query  = "SELECT `shot_number`,`end_time`  FROM esther_reports ORDER BY  `shot_number` DESC LIMIT 1";
    $result = $connection->query($query);
    $row = $result->fetch_array(MYSQLI_NUM);
    $shot_id = $row[0];

    /*    if (!$result) echo "INSERT  failed: $query<br>" .
      $connection->error . "<br><br>";
    */
    //    $shot_id=get_post($connection, 'shot_id_post');
    echo "shot_id: ". $shot_id. "<br>";
    //echo "shot_id_post: ". get_post($connection, 'shot_id_post');//  $_POST['shot_id_post'];

    /*
    if($end_time === NULL) {
      echo "NULL end_time: ". $end_time;
    }
    */
    $anomalies = get_post($connection, 'anomalies');
    $delta_P_kistler = get_post($connection, 'DeltaP_kistler');
    $plc_sw_ver = get_post($connection, 'plc_sw_ver');

    $PT101=get_last_value($connection, 5);
    $PT201=get_last_value($connection, 9);
    $PT301=get_last_value($connection, 11);
    $PT401=get_last_value($connection, 13);
    $PT501=get_last_value($connection, 15);
    $PT801=get_last_value($connection, 6);

    $pt901_end_s1 =get_last_value($connection, 26);
    $pt901_end_o=get_last_value($connection, 27);

    $pt901_end_he1=get_last_value($connection, 28);
    $pt901_end_h=get_last_value($connection, 29);
    $pt901_end_he2=get_last_value($connection, 30);
    $mfc_201_O_sp=get_last_value($connection, 36);
    $mfc_401_H_sp=get_last_value($connection, 38);
    $mfc_601_HE1_sp=get_last_value($connection, 40);
    $mfc_601_HE2_sp=get_last_value($connection, 32);

    // $wire_voltage = get_post($connection, 'wire_voltage');
    // $wire_time = get_post($connection, 'wire_time');
    $rest_time = get_post($connection, 'rest_time');
    $range_kistler = get_post($connection, 'range_kistler');
    $ignition_regime_id= get_post($connection, 'ignition_regime_id');
    $ignition_source_id= get_post($connection, 'ignition_source_id');


    //  Esther:gas:MFC201_FVOL_SP
    //    $pt901_end_o=get_last_value($connection, 27);
    //$pt901_end_o=get_last_value($connection, 27);

    $query  = "UPDATE esther_reports SET end_time = now(), N2_bottle_final = $PT101, O2_bottle_final = $PT201, He1_bottle_final = $PT301, H_bottle_final=$PT401, ".
      "He2_bottle_final = $PT501, N2_command_bottle_final=$PT801, ".
      "pt901_end_s1=$pt901_end_s1, pt901_end_o=$pt901_end_o, pt901_end_he1=$pt901_end_he1, pt901_end_h=$pt901_end_h, pt901_end_he2=$pt901_end_he2, ".
      "rest_time=$rest_time, ".
      "mfc_201_O_sp=$mfc_201_O_sp, mfc_401_H_sp=$mfc_401_H_sp, mfc_601_HE1_sp=$mfc_601_HE1_sp, mfc_601_HE2_sp=$mfc_601_HE2_sp, ".
      "anomalies='$anomalies', delta_P_kistler=$delta_P_kistler, PLC_SW_Version='$plc_sw_ver', range_kistler=$range_kistler, ".
      "ignition_regime_id=$ignition_regime_id, ignition_source_id=$ignition_source_id ".
      "WHERE  shot_number=$shot_id";
    echo $query;
    $result = $connection->query($query);
    if (!$result) die ("Database update failed: " . $connection->error);

 }

  //echo <<<_END
  //<p>The Insert Id msqli was: $shot_id </p>
//_END;

  $query  = "SELECT manager_name, esther_reports.* FROM esther_reports NATURAL JOIN esther_managers ORDER BY shot_number DESC LIMIT 1";
  $result = $connection->query($query);

  if (!$result) die ("Database access failed: " . $connection->error);

    $row = $result->fetch_array(MYSQLI_NUM);

 echo <<<_END
<p>Last Record</p>
  <pre>
    manager_name $row[0]
    shot_number $row[1]
    manager_id $row[2]
    start_time $row[3]
    end_time $row[4]
    ambient_temperature $row[5]
  </pre>
_END;


if($row[4] === NULL) { // end time not present
    //    echo "end_time: ". $end_time;
   //echo "shot_id: ". $shot_id;

   echo <<<_END
<form action="new_report.php" method="post">
  <input type="hidden" name="endrecord" value="yes">
  <input type="hidden" name="shot_id_post" value="$shot_id">
  <p>Rest Time: <input type="number" name="rest_time" value="5" step="1" min="0"> (min)</p>
  <p>Delta P Kistler: <input type="number" name="DeltaP_kistler" value="200" step="0.1" min="0"> (Bar)
      Range Kistler: <input type="number" name="range_kistler" value="500" step="1.0" min="10"> (Bar)</p>
  <p>PLC Software Version: <input type="text" name="plc_sw_ver" value="v4.04"></p>
  <p>Anomalies:<textarea name="anomalies" rows="4" cols="40"> None </textarea> </p>
  <p>Ignition Regime <select name="ignition_regime_id">
    <option value="1">No Ignition</option>
    <option value="2" selected>Single Slope Ignition</option>
    <option value="3">Dual Slope Ignition</option>
    <option value="4">Single Slope Detonation</option>
    <option value="5">Dual Slope Detonation</option>
  </select> </p>
  <p>Ignition Source<select name="ignition_source_id">
    <option value="1">Full Wire</option>
    <option value="2">Half Wire</option>
    <option value="3" selected>Laser Ignition no Lens</option>
    <option value="4">Laser Ignition with Lens</option>
  </select> </p>
  <input type="submit" value="END RECORD">
</form>
_END;

}
//
else{
 echo <<<_END
<form action="new_report.php" method="post">
  <p>Manager <select name="manager_id">
    <option value="1">M&aacute;rio Lino</option>
    <option value="2" >Bernardo Carvalho</option>
    <option value="3" selected >Rafael Rodrigues</option>
  </select> </p>

  <p> Bombe Volume: <select name="bombe_volume">
    <option value="1.86"> 1.86 (Liter)</option>
    <option value="3.02"> 3.02 (Liter)</option>
    <option value="50.35" selected > 50.35 (Liter)</option>
  </select> </p>

  <p>AMBIENT TEMPERATURE <input type="number" name="ambient_temp" value="18.0" step="0.01" min="5"  max=”40″> (&#176;C)</p>
  <p>AMBIENT PRESSURE <input type="number" name="ambient_press" value="1013.0"> (mBAR)</p>
  <p>AMBIENT HUMIDITY <input type="number" name="ambient_hum" value="50.0" step="0.1" min="10"  max=”100″> (&#37;)</p>
  <p>FILLING PRESSURE <input type="number" name="filling_pressure_sp" value ="24.0" step="0.1" min="0"  max=”100″> (Bar)</p>
  <p>He RATIO <input type="number" name="He_ratio_sp" value="8.0" step="0.01" min="0"  max=”100″> </p>
  <p>H2 RATIO <input type="number" name="H2_ratio_sp" value="2.0" step="0.01" min="0"  max=”10″> </p>
  <p>O2 RATIO <input type="number" name="O2_ratio_sp" value="1.2" step="0.01" min="0"  max=”10″> </p>
  <input type="hidden" name="newrecord" value="yes">
  <input type="submit" value="INSERT NEW RECORD">
</form>
_END;

}

  $result->close();
  $connection->close();

?>


 </body>
</html>

