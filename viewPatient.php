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
    <link href="style.css" type="text/css" rel="stylesheet">
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
          echo "<h1> Patient : ".$FIRST_NAME."  ".$NAME."</h1>";
          echo "<br/>\n";
        }
/*** The vital signs ***/
  echo "<h2 id=\"vitalsigns\">Vital Signs</h2>";
  // set the buttons to choose one of the vital signs available
        echo "<br/>Choose what to display: &nbsp;";
        $sql = "select sign_name from sign";
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
            echo "<div class = \"".$line['sign_name']."\">\n".
              "\t".$line['value']. " at ".$line['time']."\n".
              "</div>\n";
          }
					if($vital_sign_NA){
						echo "<i>&nbsp;&nbsp;No vital signs for patient ".$NAME.".</i><br>\n";
					}


  // set the controls to add a vital sign
    //set a dropdown list for available vital signs
        echo "<br/><h3 id=\"addSign\">Add a vital sign measurement</h3>";
?>
    <form method="GET" action="viewPatient.php">
        <select name = "dropdown_vs">
<?php
          $sql = "select sign_name from sign";
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
        <input type="text" name="note" placeholder="note about value" value="" size="18" />
        
<?php
    //set the submit button for the new vital sign, including a hidden field containing the patient's ID to assure the patient isn't lost on reload
?>
        <input type="submit" name="AddValue" value="add and display"/>
        <input type="hidden" name="id" value="<?php echo $PATIENT_ID?>"/>
    </form>    
<?php
if(isset($_GET['newMeasurement'])){
  $sql= "select signID from sign where sign_name='".$_GET['dropdown_vs']."'";
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
  changeH3('$_GET[\'dropdown_vs\']');
}


/*** The Medicines ***/
  echo "<br/><br/>\n
    <h2 id=\"medicines\">Medicines taken</h2>";
  // get the patient's medicines and display them in a list
    $sql = "select patient.patientID, medicament.medicament_name, medicine.quantity, medicine.time, medicine.note FROM patient, medicament, medicine WHERE patient.patientID=medicine.patientID AND medicine.medicamentID=medicament.medicamentID AND patient.name='".$NAME."'";
    $medicines = $dbh->query($sql);
    $medicine_NA = true;
    while($medicine = $medicines->fetch()){
      $medicine_NA = false;
      if(isset($medicine['note'])){
        echo $medicine['quantity']." ".$medicine['medicament_name']." at ".$medicine['time']." (note from staff: ".$medicine['note'].").";
      } else{
        echo $medicine['quantity']." ".$medicine['medicament_name']." at ".$medicine['time'].".";
      }
      echo "<br>\n";
    }
    if($medicine_NA){
      echo "<i>&nbsp;&nbsp;No medicines for patient ".$NAME.".</i><br>\n";
    }

  // set the controls to add a medicine
    echo "<br/><h3 id=\"addMed\">Add a medicine</h3>";
    //set the text field for the drug name
?>
        <form method="GET" action="viewPatient.php">
            <input type="text" name="newMed" placeholder="drug name" value="" size="18" />

<?php

//set a dropdown list for unit
?>
            <select name = "dropdown_unit">
<?php
              $sql = "select unit from medicament";
              $result = $dbh->query($sql);
              while($med = $result->fetch()){
                echo "<option value = \"".$med['unit']."\"> ".$med['unit']."</option>\n";
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
    //set the submit button for the new medicine, including a hidden field containing the patient's ID to assure the patient isn't lost on reload
?>
            <input type="hidden" name="id" value="<?php echo $PATIENT_ID?>"/>
            <input type="submit" name="AddMed" value="add"/>
        </form>


    <?php
    if(isset($_GET['newMeasurement'])){
      $sql= "select medicamentID from medicament where medicament_name='".$_GET['dropdown_med']."'";
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
      changeH3('$_GET[\'dropdown_vs\']');
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


?>



<br/><br/><br/>
<i><a href="logout.php">Logout</a></i> 

















<form>
  <input type="text" name="myText" value="Norway" selectBoxOptions="Canada;Denmark;Finland;Germany;Mexico;Norway;Sweden;United Kingdom;United States">
	<input type="text" name="myText2" value="" selectBoxOptions="Amy;Andrew;Carol;Jennifer;Jim;Tim;Tommy;Vince">
</form>

<script type="text/javascript">
createEditableSelect(document.forms[0].myText);
createEditableSelect(document.forms[0].myText2);
</script>

</body>
</html>

