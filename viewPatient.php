<?php
session_start();

// First, we test if user is logged. If not, goto main.php (login page).
if(!isset($_SESSION['user'])){
  header("Location: main.php");
  exit();
}

// set the html head
?>
<html>
  <head>
    <title>Patient's vital signs</title>
    <link rel="stylesheet" type="text/css" href="additional.css" >
    <script src="editableSelect.js"></script>
  </head>
  <body>

<?php
  include('pdo.inc.php');

  try {
      $dbh = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
      $PATIENT_ID=0;
      if(isset($_GET['id'])){
        $PATIENT_ID = (int)($_GET['id']);
      }
/*** if the selected patientID does not exist, state that  ***/
      if($PATIENT_ID <=0){
        echo "<h1>The patient does not exist</h1>";
      }else{

/*** echo a message saying we have connected: Display the Patient Name in the Header ***/
        $sql0 = "SELECT name, first_name
        FROM patient
        WHERE patient.patientID = :patientID";
        $statement0 = $dbh->prepare($sql0);
        $statement0->bindParam(':patientID', $PATIENT_ID, PDO::PARAM_INT);
        $result0 = $statement0->execute();
        while($line = $statement0->fetch()){
          $NAME = $line['name'];
          $FIRST_NAME = $line['first_name'];
	  echo "<div id='patientheader'><div style='float:left;'><h1> Patient : ".$FIRST_NAME."  ".$NAME."</h1></div><div style='float:right; padding-right:10px; padding-top:10px;'>
		  <form action='/listPatients.php' method='get'>
		    <button type='submit'>Patient List</button>
                  </form>
		  </div></div>";
          echo "<br/>\n";
        }
/*** The vital signs ***/
  echo "<h2 id=\"vitalsigns\">Vital Signs</h2>";
  // set the buttons to choose one of the vital signs available
        echo "<br/>Choose what to display: &nbsp;";
        $sql = "SELECT sign_name FROM sign";
        $result = $dbh->query($sql);
        while($vs = $result->fetch()){
          $vs_stripped = str_replace(' ', '', $vs['sign_name']);
          echo "<input type=\"button\" ".
          "value=\"".$vs['sign_name']."\" ".
          "name=\"btn".$vs_stripped."\" ".
          "onClick=\"changeH3('".$vs['sign_name']."')\"/>".
          "\n";

        }

?>
<script type="text/javascript">
      function changeH3($vitalSignsToShow) {

  //put all vital signs into an array, and set their display property to 'none' (i.e., hide them)
        var arrDiv = Array.prototype.slice.call(document.getElementsByTagName("div"));
        for(var i in arrDiv){
          arrDiv[i].style.display = 'none';
        }

    //display the vital sign type (according to what button was pressed) as a header
        document.getElementById("sign").textContent = $vitalSignsToShow;

    //put all vital signs of the chosen type into an array, and set their display property to 'block' (i.e., unhide them)
        var arrShow = Array.prototype.slice.call(document.getElementsByClassName($vitalSignsToShow));
        for(var i in arrShow){
          arrShow[i].style.display = 'block';
        }
      }
    </script>
    <br />
      <?php

    //Insert a <h3> placeholder for displaying the vital sign type chosen according to what button was pressed
        echo "<h3 id=\"sign\"></h3>\n";


  
        $sql = "SELECT name, first_name, value, time, sign_name
        FROM patient, vital_sign, sign
        WHERE patient.patientID = vital_sign.patientID
          AND vital_sign.signID = sign.signID 
          AND patient.patientID = :patientID";

        $statement = $dbh->prepare($sql);
        $statement->bindParam(':patientID', $PATIENT_ID, PDO::PARAM_INT);
        $result = $statement->execute();

  // display the vital signs
          $vital_sign_NA = true;
          while($line = $statement->fetch()){
            $vital_sign_NA = false;
            echo "<div class = \"".$line['sign_name']." vitalsignsdiv\">\n".
              "\t".$line['value']. " at ".$line['time']."\n".
              "</div>\n";
          }
					if($vital_sign_NA){
						echo "<i>&nbsp;&nbsp;No vital signs for patient ".$NAME.".</i><br>\n";
					}


  // set the controls to add a vital sign

        echo "<br/><h3 id=\"addSign\">Add a vital sign measurement</h3>";
    // start with including a hidden field containing the patient's ID to assure the patient isn't lost on reload
?>
      <form method="GET" action="viewPatient.php">
        <input type="hidden" name="id" value="<?php echo $PATIENT_ID?>"/>


<?php
    //set a dropdown list for available vital signs
?>
        <select name = "dropdown_vs">
<?php
          $sql = "SELECT sign_name FROM sign";
          $result = $dbh->query($sql);
          while($vs = $result->fetch()){
            echo "<option value = \"".$vs['sign_name']."\"> ".$vs['sign_name']."</option>\n";
          }
?>       
        </select>

<?php
    //set text input fields for value and note for the chosen new vital sign
    //!!INPUT VALIDATION needed!!
?>
        <input type="text" name="newMeasurement" placeholder="value" value="" size="9" />
        <input type="text" name="note" placeholder="note about measurement" value="" size="27" />
        
<?php
    //set the submit button for the new vital sign
?>
        <input type="submit" name="AddValue" value="add"/>
    </form>    
<?php


    //add the new information into the database
    if(isset($_GET['newMeasurement'])){
      $sql= "SELECT signID FROM sign WHERE sign_name='".$_GET['dropdown_vs']."'";
      $result = $dbh->query($sql);
        
      $vsID=$result->fetch();
            
      $sql = "INSERT
        INTO vital_sign
      VALUES (
          '',
          '".$PATIENT_ID."',
          '".$vsID[0]."',
          '".$_GET['newMeasurement']."',
          CURRENT_TIMESTAMP,
          '".$_GET['note']."'
        )";
      $dbh->exec($sql);
      //changeH3('$_GET[\'dropdown_vs\']');
    }


/*** The Medicines ***/
  echo "<br/><br/>\n
    <h2 id=\"medicines\">Medicines</h2>";
  // get the medicines that have been admistered to the patient and display them in a list
  echo "<h4 id=\"administered\">Medicines taken</h2>";
    // first, make an array[][] $medPscr_arr of pairs of medicineID and prescriber (note: key=>value array NOT possible, as key not continuous!)
      $sql = "SELECT
        medicine.medicineID,
        staff.name
      FROM medicine, staff, medicament
      WHERE staff.staffID=medicine.staffID_physician
        AND medicine.medicamentID=medicament.medicamentID
      ";
    $medsPrescr = $dbh->query($sql);
    $i=0;
    while($medPscr = $medsPrescr->fetch()){
      $medPscr_arr[$i][0] = $medPscr['medicineID'];
      $medPscr_arr[$i][1] = $medPscr['name'];
      $i++;
    }  
    //then
    // - get all the other information about the administrations (med name, unit, qty, adminstrating staff member, time, ..)
    // - and combine them with the prescriber information from the array[][] $medPscr_arr above
    // - then display this now completed list
  $sql = "SELECT
      medicament.medicament_name,
      medicament.unit,
      medicine.medicineID,
      medicine.quantity,
      medicine.time,
      medicine.note,
      staff.name,
      function.function_name
    FROM patient, medicament, medicine, staff, function
    WHERE patient.patientID=medicine.patientID
      AND medicine.medicamentID=medicament.medicamentID
      AND staff.staffID=medicine.staffID_nurse
      AND staff.fonctionID=function.functionID
      AND patient.name='".$NAME."'
    ";
    $medicines = $dbh->query($sql);
    $medicine_NA = true;
    $i=0;
    while($medicine = $medicines->fetch()){
      $medicine_NA = false;
      while($medPscr_arr[$i][0]!=$medicine['medicineID'] && $i <= sizeof($medPscr_arr)){
        $i++;
      }
      echo $medicine['medicament_name']." (prescribed by Dr. ".$medPscr_arr[$i][1]."): ".$medicine['quantity']." ".$medicine['unit']." at ".$medicine['time']." (administated by ".$medicine['function_name']." ".$medicine['name'];
      if(isset($medicine['note'])){
        echo ": ".$medicine['note'];
      }
      echo ").";
      echo "<br>\n";
      
    }
    if($medicine_NA){
      echo "<i>&nbsp;&nbsp;No medicines for patient ".$NAME.".</i><br>\n";
    }

  // set the controls to add a medicine
    echo "<br/><h3 id=\"addMed\">Add a prescribed administration of a medicine</h3>";
    // start with including a hidden field containing the patient's ID to assure the patient isn't lost on reload
    ?>
        <form method="GET" action="viewPatient.php">
            <input type="hidden" name="id" value="<?php echo $PATIENT_ID?>"/>


<?php
    //set a dropdown list for the new prescribed administration of a medicine
?>
    <select required name = "dropdown_newMed">
<?php
      echo "<option value=\"\" disabled selected>Select medicament...</option>";
      $sql = "SELECT medicament_name
              FROM medicament";
      $result = $dbh->query($sql);
      while($meds = $result->fetch()){
        $med[] = $meds['medicament_name'];
      }
      $med_filtered = array_unique($med);
      sort($med_filtered);
      foreach($med_filtered as $med_itm){
        echo "<option value = \"".$med_itm."\"> ".$med_itm."</option>\n";
      }  
?>       
    </select>

<?php


    //set a dropdown list for unit
?>
            <select name = "dropdown_unit">
<?php
              $sql = "SELECT unit FROM medicament";
              $result = $dbh->query($sql);
              while($meds = $result->fetch()){
                $units[] = $meds['unit'];
              }
              $units_filtered = array_unique($units);
              arsort($units_filtered);
              foreach($units_filtered as $unit){
                echo "<option value = \"".$unit."\"> ".$unit."</option>\n";
              }           
?>       
            </select>


<?php
    //set a dropdown list for quantity
?>
            <select name = "dropdown_qty">
<?php
              for($i=1; $i<=10;$i++){
                echo "<option value = \"".$i."\"> ".$i."</option>\n";
              }
?>       
            </select>


<?php
    //set a dropdown list for the prescribing staff member (from which staff groups a prescriber can be chosen can be pre-set by adding function_name AND-statements in the WHERE-clause of the sql query.)
    ?>
    <select required name = "dropdown_prescr">
<?php
      echo "<option value=\"\" disabled selected>Select prescriber...</option>";
      $sql = "SELECT name
              FROM staff, function
              WHERE staff.fonctionID=function.functionID
                AND function.function_name='Physician'";
      $result = $dbh->query($sql);
      $staff = array();
      while($names = $result->fetch()){
        $staff[] = $names['name'];
      }
      $staff_filtered = array_unique($staff);
      sort($staff_filtered);
      foreach($staff_filtered as $staff_mbr){
        echo "<option value = \"".$staff_mbr."\"> ".$staff_mbr."</option>\n";
      }  
?>       
    </select>

<?php


    //set a dropdown list for the administrating staff member (from which staff groups someone can administrate a medicine can be pre-set by adding function_name AND-statements in the WHERE-clause of the sql query.)
    ?>
    <select required name = "dropdown_adm">
<?php
      echo "<option value=\"\" disabled selected>Administr. staff member...</option>";
      $sql = "SELECT name
              FROM staff, function
              WHERE staff.fonctionID=function.functionID
                AND function.function_name='Nurse'";
      $result = $dbh->query($sql);
      $staff = array();
      while($names = $result->fetch()){
        $staff[] = $names['name'];
      }
      $staff_filtered = array_unique($staff);
      sort($staff_filtered);
      foreach($staff_filtered as $staff_mbr){
        echo "<option value = \"".$staff_mbr."\"> ".$staff_mbr."</option>\n";
      }  
?>       
    </select>

<?php


    //set the text field for a note about the new medicine
    //  DODO: linebreak %0D%0A and space + should be filtered..!
?>
    <textarea name="note_newMed" placeholder="note (opt.)" value="" cols="18" rows="4"></textarea>
<?php


//set the submit button for the new medicine
?>
            <input type="submit" name="AddMed" value="add"/>
        </form>

<?php
        //add the new information into the database
    if(isset($_GET['dropdown_newMed'])){
      //get the medicament's ID
      $sql= "SELECT medicamentID FROM medicament WHERE medicament_name='".$_GET['dropdown_newMed']."'";
      $result = $dbh->query($sql);
      $medID=$result->fetch();

      //get the administrating staff member's ID
      $sql= "SELECT staffID FROM staff WHERE name='".$_GET['dropdown_adm']."'";
      $result = $dbh->query($sql);
      $admID=$result->fetch();

      //get the prescribing staff member's ID
      $sql= "SELECT staffID FROM staff WHERE name='".$_GET['dropdown_prescr']."'";
      $result = $dbh->query($sql);
      $prscrID=$result->fetch();
            
      $sql = "INSERT
        INTO medicine
      VALUES (
          '',
          CURRENT_TIMESTAMP,
          '".$_GET['dropdown_qty']."',
          '".$medID[0]."',
          '".$PATIENT_ID."',
          '".$admID[0]."',
          '".$prscrID[0]."',
          '".$_GET['note_newMed']."'
        )";
      $dbh->exec($sql);
    }
?>
    <br/>
<?php     





      }
    $dbh = null;
}
catch(PDOException $e)
{

    /*** echo the sql statement and error message ***/
    echo $e->getMessage();
}


echo '<br>
      <div id="patientfooter">
      <form action="/logout.php" method="get">
      <button style="float:right;" type="submit">Logout as '.$_SESSION['user'];
echo '</button>
      </form>
      </div>';

?>
</body>
</html>


